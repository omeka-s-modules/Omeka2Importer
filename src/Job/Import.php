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

        $Omeka2ImportJson = array(
                            'o:job'         => array('o:id' => $this->job->getId()),
                            'comment'       => 'Job started',
                            'added_count'   => 0,
                            'updated_count' => 0
                          );

        $response = $this->api->create('omekaimport_imports', $Omeka2ImportJson);
        $importRecordId = $response->getContent()->id();

        $options = $this->job->getArgs();
        if ($this->getArg('importCollections', false)) {
            $this->importCollections($options);
        } else {
            $this->importItems($options);
        }

        $comment = $this->getArg('comment');
        $Omeka2ImportJson = array(
                            'o:job'         => array('o:id' => $this->job->getId()),
                            'comment'       => $comment,
                            'added_count'   => $this->addedCount,
                            'updated_count' => $this->updatedCount
                          );

        $response = $this->api->update('omekaimport_imports', $importRecordId, $Omeka2ImportJson);
    }
    
    protected function importCollections($options = array())
    {
        $page = 1;
        //use this to keep track of all the added item sets from collections,
        //so that when it's all done I can add the dcterms:hasPart property values
        //to the 'parent' item set chosen for the import
        //$collectionItemSetIds = array();
        $itemSetUpdateData = array('dcterms:hasPart' => array());
        $dctermsHasPartId = $this->getPropertyId('dcterms:hasPart');
        do {
            $response = $this->client->collections->get(null, array('page' => $page));
            $collectionsData = json_decode($response->getBody(), true);
            foreach ($collectionsData as $collectionData) {

                $omekaCollectionId = $collectionData['id'];
                $options['collectionId'] = $omekaCollectionId;


                $collectionImportRecord = $this->importRecord($omekaCollectionId, 'collection');
                if ($collectionImportRecord) {
                    $collectionImportRecordJson = array('o:job' => 
                                                            array('o:id' => $this->job->getId())
                                                       );
                    
                    $updateImportRecordResponse = $this->api->update(
                                                    'omekaimport_records',
                                                    $collectionImportRecord->id(),
                                                    $collectionImportRecordJson);
                } else {
                    
                    $itemSetJson = $this->buildResourceJson($collectionData, $options);
                    $response = $this->api->create('item_sets', $itemSetJson);
                    $itemSetReference = $response->getContent();
                    $itemSetId = $itemSetReference->id();
                    $collectionImportRecordJson = $this->buildImportRecordJson(
                                                     $omekaCollectionId,
                                                     $itemSetReference,
                                                     'collection'
                                               );
                    $response = $this->api->create('omekaimport_records', $collectionImportRecordJson);
                    
                    
                    //optional for creating hasPart relations to parent item set
                    if (isset($options['itemSet'])) {
                        $itemSetUpdateData['dcterms:hasPart'][] = array(
                                'property_id'       => $dctermsHasPartId,
                                'value_resource_id' => $itemSetId
                                );
                    }
                    $options['collectionItemSet'] = $itemSetId;
                }
                
                $this->importItems($options);
            }
            $page++;
        //} while ($this->hasNextPage($response));
        } while (false); // debugging
        
        //add dcterms:hasPart data to the 'parent' item set for
        //each of the imported (as item sets) collections
        if (! empty($options['itemSet'])) {
            $this->api->update('item_sets', $options['itemSet'], $itemSetUpdateData, array(), true);
        }
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

                if (count($toCreate) > 0) {
                    $this->createItems($toCreate);
                }
                if (count($toUpdate) > 0) {
                    $this->updateItems($toUpdate);
                }

            $page++;
        //} while ($this->hasNextPage($clientResponse));
        } while (false); //debugging
    }

    protected function createItems($toCreate) 
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, array(), true);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);
        $createImportRecordsJson = array();
        
        foreach($createContent as $remoteId => $resourceReference) {
            $createImportRecordsJson[] = $this->buildImportRecordJson($remoteId, $resourceReference);
        }
        $createImportRecordResponse = $this->api->batchCreate('omekaimport_records', $createImportRecordsJson, array(), true);
    }

    protected function updateItems($toUpdate) 
    {
        //  batchUpdate would be nice, but complexities abound. See https://github.com/omeka/omeka-s/issues/326
        $updateResponses = array();
        foreach ($toUpdate as $importRecordId=>$itemJson) {
            $this->updatedCount = $this->updatedCount + 1;
            $updateResponses[$importRecordId] = $this->api->update('items', $itemJson['id'], $itemJson);
        }
        //only updating the job id for all
        $importRecordUpdateJson = array('o:job' => array('o:id' => $this->job->getId()),
                           );
        foreach ($updateResponses as $importRecordId => $resourceReference) {
            $updateImportRecordResponse = $this->api->update('omekaimport_records', $importRecordId, $importRecordUpdateJson);
        }
    }
    
    protected function buildImportRecordJson($remoteId, $resourceReference, $type = 'item')
    {
        $recordJson = array('o:job'       => array('o:id' => $this->job->getId()),
                            'endpoint'    => $this->endpoint,
                            'remote_type' => $type,
                            'remote_id'   => $remoteId
                            );
        if ($type == 'item') {
            $recordJson['o:item'] = array('o:id' => $resourceReference->id());
        }
        
        if ($type == 'collection') {
            $recordJson['o:item_set'] = array('o:id' => $resourceReference->id());
        }
        return $recordJson;
    }
    
    protected function buildResourceJson($importData, $options = array())
    {
        $resourceJson = array();
        $resourceJson['remote_id'] = $importData['id'];
        if (isset($options['collectionItemSet'])) {
            $resourceJson['o:item_set'] = array();
            $resourceJson['o:item_set'][] = array('o:id' => $options['collectionItemSet']);
        } else if (isset($options['itemSet'])) {
            $resourceJson['o:item_set'] = array();
            $resourceJson['o:item_set'][] = array('o:id' => $options['itemSet']);
        }
        $resourceClassId = null;
        if (isset($importData['item_type'])) {
            $itemTypeName = $importData['item_type']['name'];
            if (array_key_exists($itemTypeName, $this->itemTypeMap)) {
                
                //caching looked-up id in the same array from item_type_maps under 'id' key
                if (isset($this->itemTypeMap[$itemTypeName]['id'])) {
                    $resourceClassId = $this->itemTypeMap[$itemTypeName]['id'];
                } else {
                    $class = $this->itemTypeMap[$itemTypeName]['class'];
                    $exploded = explode(':', $class);
                    $resourceClassesResponse = $this->api->search(
                            'resource_classes',
                            array(
                                  'vocabulary_prefix' => $exploded[0],
                                  'local_name'        => $exploded[1]
                            ));
                    $resourceClassId = $resourceClassesResponse->getContent()[0]->id();
                    //cache the id (gotta cache'em all)
                    $this->itemTypeMap[$itemTypeName]['id'] = $resourceClassId;
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

    protected function importRecord($remoteId, $remoteType = 'item')
    {
        //see if the item has already been imported
        $response = $this->api->search('omekaimport_records',
                                array('remote_id' => $remoteId, 'remote_type' => $remoteType));
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
