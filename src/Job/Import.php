<?php
namespace Omeka2Importer\Job;

use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;


class Import extends AbstractJob
{
    protected $client;

    protected $endpoint;

    protected $api;

    protected $termIdMap;

    protected $addedCount;

    protected $updatedCount;

    protected $itemTypeMap;

    protected $itemTypeElementMap;

    public function perform()
    {
        include('item_type_maps.php');
        $this->itemTypeMap = $itemTypeMap;
        $this->itemTypeElementMap = $itemTypeElementMap;
        $this->addedCount = 0;
        $this->updatedCount = 0;
        $this->endpoint = rtrim($this->getArg('endpoint'), '/'); //make this a filter?
        $this->client = $this->getServiceLocator()->get('Omeka2Importer\Omeka2Client');
        $this->client->setApiBaseUrl($this->endpoint);
        $this->client->setKey($this->getArg('key'));
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->prepareTermIdMap();
        $options = $this->job->getArgs();
        if (isset($options['itemSet'])) {
            $options['itemSets'] = array($options['itemSet']);
        }
        if ($this->getArg('importCollections', false)) {
            $this->importCollections($options);
        } else {
            $options['importFiles'] = true;
            $this->importItems($options);
        }
        
        $comment = $this->getArg('comment');
        $Omeka2ImportJson = array(
                            'o:job'         => array('o:id' => $this->job->getId()),
                            'comment'       => $comment,
                            'added_count'   => $this->addedCount,
                            'updated_count' => $this->updatedCount
                          );
        
        echo 'before import';
        $response = $this->api->create('omeka2imports', $Omeka2ImportJson);
        echo 'after import create';
    }
    
    protected function importCollections($options = array())
    {
        $page = 1;
        do {
            $response = $this->client->collections->get(null, array('page' => $page));
            $collectionsData = json_decode($response->getBody(), true);
            foreach ($collectionsData as $collectionData) {
                $itemSetJson = $this->buildResourceJson($collectionData, $options);
                $response = $this->api->create('item_sets', $itemSetJson);
                $itemSetId = $response->getContent()->id();
                $omekaCollectionId = $collectionData['id'];
                $options['collectionId'] = $omekaCollectionId;
                $options['itemSets'][] = $itemSetId;
                $this->importItems($options);
            }
            $page++;
        //} while ($this->hasNextPage($response));
        } while (false);
    }

    protected function importItems($options = array())
    {
        $page = 1;
        $params = array();
        //if importing by collections from Omeka 2, the collection to use as
        //the param for querying the Omeka 2 API
        if (isset($options['collectionId'])) {
            $params['collection'] = $options['collectionId'];
        }
        do {
                $params['page'] = $page;
                $clientResponse = $this->client->items->get(null, $params);
                $itemsData = json_decode($clientResponse->getBody(), true);
                $toCreate = array();
                $toUpdate = array();
                foreach($itemsData as $itemData) {

                    $itemJson = array();
                    $itemJson = $this->buildResourceJson($itemData, $options);
                    //confusingly named, importRecord is the record of importing the item
                    $importRecord = $this->importRecord($itemData['id']);
                    
                    //separate the items to create from those to update
                    if ($importRecord) {
                        //add the Omeka S item id to the itemJson
                        //and key by the importRecordid for reuse
                        //in both updating the item itself, and the importRecord
                        $itemJson['id'] = $importRecord->item()->id(); 
                        $toUpdate[$importRecord->id()] = $itemJson;
                    } else {
                        //key by the remote id for batchCreate
                        $toCreate[$itemData['id']] = $itemJson;
                    }
                }
                
            $this->createItems($toCreate);
            $this->updateItems($toUpdate);

            $page++;
        //} while ($this->hasNextPage($clientResponse));
        } while(false);
    }

    protected function createItems($toCreate) 
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, array(), true);
        $this->addedCount = $this->addedCount + count($createResponse);
        $createImportRecordsJson = array();
        $createContent = $createResponse->getContent();
        foreach($createContent as $remoteId => $resourceReference) {
            $createImportRecordsJson[] = $this->buildImportRecordJson($remoteId, $resourceReference);
        }
        $createImportRecordResponse = $this->api->batchCreate('omeka2items', $createImportRecordsJson, array(), true);
    }

    protected function updateItems($toUpdate) 
    {
        //  batchUpdate would be nice, but complexities abound. See https://github.com/omeka/omeka-s/issues/326
        $updateResponses = array();
        foreach ($toUpdate as $importRecordId=>$itemJson) {
            $updateResponses[$importRecordId] = $this->api->update('items', $itemJson['id'], $itemJson);
        }
        //only updating the job id for all
        $importRecordUpdateJson = array('o:job' => array('o:id' => $this->job->getId()),
                           );
        foreach ($updateResponses as $importRecordId => $resourceReference) {
            echo $importRecordId . ' ';
            $updateImportRecordResponse = $this->api->update('omeka2items', $importRecordId, $importRecordUpdateJson);
        }
    }
    
    protected function buildImportRecordJson($remoteId, $resourceReference)
    {
        $recordJson = array('o:job'     => array('o:id' => $this->job->getId()),
                            'endpoint'  => $this->endpoint,
                            'o:item'    => array('o:id' => $resourceReference->id()),
                            'remote_id' => $remoteId
                            );
        return $recordJson;
    }
    
    protected function buildResourceJson($importData, $options = array())
    {
        $resourceJson = array();
        $resourceJson['remote_id'] = $importData['id'];
        if (isset($options['itemSets'])) {
            $resourceJson['o:item_set'] = array();
            foreach ($options['itemSets'] as $itemSetId) {
                $resourceJson['o:item_set'][] = array('o:id' => $itemSetId);
            }
        }
        $resourceClassId = null;
        if (isset($importData['itemType'])) {
            if (array_key_exists($importData['itemType'], $this->itemTypeMap)) {
                //caching looked-up id in the same array from item_type_maps under 'id' key
                if (isset($this->itemTypeMap[$importData['itemType']]['id'])) {
                    $resourceClassId = $this->itemTypeMap[$importData['itemType']]['id'];
                } else {
                    $class = $this->itemTypeMap[$importData['itemType']];
                    $exploded = explode(':', $class);
                    $resourceClasses = $this->api->search(
                            'resource_classes',
                            array(
                                  'vocabulary_prefix' => $exploded[0],
                                  'local_name'        => $exploded[1]
                            ));
                    $resourceClassId = $resourceClasses[0]->id();
                    //cache the id (gotta cache'em all)
                    $this->itemTypeMap[$importData['itemType']]['id'] = $resourceClassId;
                }
            }
            $resourceJson['o:resource_class'] = array('o:id' => $resourceClassId);
        }
        
        $resourceJson = array_merge($resourceJson, $this->buildPropertyJson($importData));
        $resourceJson = array_merge($resourceJson, $this->buildMediaJson($importData));
        return $resourceJson;
    }

    protected function buildMediaJson($importData)
    {
        //another query to get the filesData from the importData
        $itemId = $importData['id'];
        $response = $this->client->files->get(array('item' => $itemId));
        $filesData = json_decode($response->getBody(), true);
        $mediaJson = array('o:media' => array());
        foreach($filesData as $fileData) {
            $fileJson = array(
                'o:type'     => 'url',
                'o:source'   => $fileData['file_urls']['original'],
                'ingest_url' => $fileData['file_urls']['original'],
            );
            $fileJson = array_merge($fileJson, $this->buildPropertyJson($fileData));
            $mediaJson['o:media'][] = $fileJson;
        }
        return $mediaJson;
    }

    protected function buildPropertyJson($importData) {
        $propertyJson = array();
        foreach($importData['element_texts'] as $elTextData) {
            $elementSetName = $elTextData['element_set']['name'];
            $elementName = $elTextData['element']['name'];
            switch ($elementSetName) {
                case 'Dublin Core':
                    $term = "dcterms:" . strtolower($elementName);
                    break;
                case 'Item Type Metadata':
                    if (array_key_exists($elementName, $this->itemTypeElementMap)) {
                        $term = $this->itemTypeElementMap[$elementName];
                    } else {
                        $term = false;
                    }
                    break;
                default:
                    $term = false;
            }
            if ($term) {
                $propertyId = $this->getPropertyId($term);
                $value = strip_tags($elTextData['text']);
                if ($propertyId) {
                    $propertyJson[$term][] = array(
                            '@value'      => $value,
                            'property_id' => $propertyId
                            );
                }
            }
        }
        return $propertyJson;
    }

    protected function prepareTermIdMap()
    {
        $this->termIdMap = array();
        $properties = $this->api->search('properties', array(
            'vocabulary_namespace_uri' => 'http://purl.org/dc/terms/'
        ))->getContent();
        foreach ($properties as $property) {
            $term = "dcterms:" . $property->localName();
            $this->termIdMap[$term] = $property->id();
        }

        $properties = $this->api->search('properties', array(
            'vocabulary_namespace_uri' => 'http://purl.org/ontology/bibo/'
        ))->getContent();
        foreach ($properties as $property) {
            $term = "bibo:" . $property->localName();
            $this->termIdMap[$term] = $property->id();
        }
    }

    protected function getPropertyId($term)
    {
        return $this->termIdMap[$term];
    }

    protected function importRecord($remoteId)
    {
        //see if the item has already been imported
        $response = $this->api->search('omeka2items', array('remote_id' => $remoteId));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $importedItem = $content[0];
    }

    protected function hasNextPage($response)
    {
        $headers = $response->getHeaders();
        $linksHeaders = $response->getHeaders()->get('Link')->toString();
        return strpos($linksHeaders, 'rel="next"');
    }
}
