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

    public function perform()
    {
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
        $response = $this->api->create('omeka2imports', $Omeka2ImportJson);
    }
    
    protected function importCollections($options = array())
    {
        $page = 1;
        do {
            $response = $this->client->collections->get(null, array('page' => $page));
            $collectionsData = json_decode($response->getBody(), true);
            foreach ($collectionsData as $collectionData) {
                unset($options['importFiles']);
                $itemSetJson = $this->buildResourceJson($collectionData, $options);
                $response = $this->api->create('item_sets', $itemSetJson);
                $itemSetId = $response->getContent()->id();
                $omekaCollectionId = $collectionData['id'];
                $options['importFiles'] = true;
                $options['collectionId'] = $omekaCollectionId;
                $options['itemSets'][] = $itemSetId;
                $this->importItems($options);
            }
            $page++;
        } while ($this->hasNextPage($response));
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
            foreach($itemsData as $itemData) {
                $itemJson = array();
                $itemJson = $this->buildResourceJson($itemData, $options);
                $importRecord = $this->importRecord($itemData['id']);
                if ($importRecord) {
                    $response = $this->api->update('items', $importRecord->item()->id(), $itemJson);
                    $importItemEntityJson = array(
                                    'o:job'         => array('o:id' => $this->job->getId()),
                                    'last_modified' => new \DateTime($itemData['modified']),
                                  );
                    $response = $this->api->update('omeka2items', $importRecord->id(), $importItemEntityJson);
                    $this->updatedCount++;
                } else {
                    $response = $this->api->create('items', $itemJson);
                    $content = $response->getContent();
                    $itemId = $content->id();
 
                    $importItemEntityJson = array(
                                    'o:job'         => array('o:id' => $this->job->getId()),
                                    'o:item'        => array('o:id' => $itemId),
                                    'endpoint'      => $this->endpoint,
                                    'last_modified' => new \DateTime($itemData['modified']),
                                    'remote_id'     => $itemData['id']
                                  );
                    $response = $this->api->create('omeka2items', $importItemEntityJson);
                    $this->addedCount++;
                }
            }
            $page++;
        } while ($this->hasNextPage($clientResponse));
    }

    protected function buildResourceJson($importData, $options = array())
    {
        $resourceJson = array();
        if (isset($options['itemSets'])) {
            $resourceJson['o:item_set'] = array();
            foreach ($options['itemSets'] as $itemSetId) {
                $resourceJson['o:item_set'][] = array('o:id' => $itemSetId);
            }
        }
        $resourceJson = array_merge($resourceJson, $this->buildPropertyJson($importData));
        if (isset($options['importFiles']) && $options['importFiles']) {
            $resourceJson = array_merge($resourceJson, $this->buildMediaJson($importData));
        }
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
                    $term = false; //for now @todo
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
