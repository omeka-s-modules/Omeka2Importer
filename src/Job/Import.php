<?php

namespace Omeka2Importer\Job;

use Omeka\Job\AbstractJob;

class Import extends AbstractJob
{
    protected $client;

    protected $endpoint;

    protected $api;

    protected $termIdMap;
    
    protected $collectionItemSetMap;

    protected $addedCount;

    protected $updatedCount;

    protected $typeMap;

    protected $elementMap;

    protected $htmlElementMap;

    protected $dctermsTitleId;

    protected $logger;
    
    protected $importRecordId;

    public function perform()
    {
        $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
        $this->collectionItemSetMap = array();
        if (is_array($this->getArg('type-class'))) {
            $this->typeMap = $this->getArg('type-class', array());
        } else {
            $this->typeMap = array();
        }

        if (is_array($this->getArg('element-property'))) {
            $this->elementMap = $this->getArg('element-property', array());
        } else {
            $this->elementMap = array();
        }

        $this->htmlElementMap = $this->getArg('html-element', array());

        $this->addedCount = 0;
        $this->updatedCount = 0;
        $this->endpoint = rtrim($this->getArg('endpoint'), '/'); //make this a filter? also, it's checked upon submission
        $this->client = $this->getServiceLocator()->get('Omeka2Importer\Omeka2Client');
        $this->client->setApiBaseUrl($this->endpoint);
        $this->client->setKey($this->getArg('key'));
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');

        //cache the dcterms:title id for reuse during mapping in buildHtmlMediaJson
        $response = $this->api->search('properties', array('term' => 'dcterms:title'));
        if (!empty($response->getContent())) {
            $dctermsTitle = $response->getContent()[0];
            $this->dctermsTitleId = $dctermsTitle->id();
        }

        $Omeka2ImportJson = array(
                            'o:job' => array('o:id' => $this->job->getId()),
                            'added_count' => 0,
                            'updated_count' => 0,
                          );

        $response = $this->api->create('omekaimport_imports', $Omeka2ImportJson);
        $this->importRecordId = $response->getContent()->id();

        $options = $this->job->getArgs();
        if ($this->getArg('importCollections', false)) {
            $this->importCollections($options);
        }
        $this->importItems($options);
        
    }

    protected function importCollections($options = array())
    {
        $page = 1;
        $itemSetUpdateData = array();
        do {
            try {
                $clientResponse = $this->client->collections->get(null, array('page' => $page));
            } catch (\Exception $e) {
                $this->logger->err((string) $e);
                continue;
            }
            if (!$clientResponse->isOK()) {
                $this->logger->err('HTTP problem: '.$clientResponse->getStatusCode().' '.$clientResponse->getReasonPhrase());
                continue;
            }
            $collectionsData = json_decode($clientResponse->getBody(), true);
            foreach ($collectionsData as $collectionData) {
                $omekaCollectionId = $collectionData['id'];
                $options['collectionId'] = $omekaCollectionId;

                $collectionImportRecord = $this->importRecord($omekaCollectionId, 'collection');
                
                $update = (bool) $this->getArg('update');
                if ($update && $collectionImportRecord) {
                    $collectionImportRecordJson = array('o:job' => array('o:id' => $this->job->getId()),
                                                       );

                    $updateImportRecordResponse = $this->api->update(
                                                    'omekaimport_records',
                                                    $collectionImportRecord->id(),
                                                    $collectionImportRecordJson);

                    $itemSet = $collectionImportRecord->itemSet();
                    if ($itemSet) {
                        $this->collectionItemSetMap[$omekaCollectionId] = $itemSet->id();
                    }
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

                    $this->collectionItemSetMap[$omekaCollectionId] = $itemSetId;
                }
            }
            ++$page;
        } while ($this->hasNextPage($clientResponse));
    }

    protected function importItems($options = array())
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');

        $page = 1;
        $params = array();
        //if importing by collections from Omeka 2, the collection to use as
        //the param for querying the Omeka 2 API
        do {
            $params['page'] = $page;
            $this->logger->info("Importing item page $page");
            
            try {
                $clientResponse = $this->client->items->get(null, $params);
            } catch (\Exception $e) {
                $this->logger->err((string) $e);
                continue;
            }
            if (!$clientResponse->isOK()) {
                $this->logger->err('HTTP problem: '.$clientResponse->getStatusCode().' '.$clientResponse->getReasonPhrase());
                continue;
            }

            $itemsData = json_decode($clientResponse->getBody(), true);
            $toCreate = array();
            $toUpdate = array();
            foreach ($itemsData as $itemData) {
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
            
            $update = (bool) $this->getArg('update');
            if ($update && count($toUpdate) > 0) {
                $this->updateItems($toUpdate);
            }

            ++$page;
                
            $comment = $this->getArg('comment');
            $Omeka2ImportJson = array(
                                'o:job' => array('o:id' => $this->job->getId()),
                                'comment' => $comment,
                                'added_count' => $this->addedCount,
                                'updated_count' => $this->updatedCount,
                              );
        
            $response = $this->api->update('omekaimport_imports', $this->importRecordId, $Omeka2ImportJson);
        } while ($this->hasNextPage($clientResponse));
    }

    protected function createItems($toCreate)
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, array(), true);
        $createContent = $createResponse->getContent();
        $this->addedCount = $this->addedCount + count($createContent);
        $createImportRecordsJson = array();

        foreach ($createContent as $remoteId => $resourceReference) {
            $createImportRecordsJson[] = $this->buildImportRecordJson($remoteId, $resourceReference);
        }

        $createImportRecordResponse = $this->api->batchCreate('omekaimport_records', $createImportRecordsJson, array(), true);
    }

    protected function updateItems($toUpdate)
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $unitOfWork = $em->getUnitOfWork();
        //  batchUpdate would be nice, but complexities abound. See https://github.com/omeka/omeka-s/issues/326
        //only updating the job id for all
        $importRecordUpdateJson = array('o:job' => array('o:id' => $this->job->getId()));
        foreach ($toUpdate as $importRecordId => $itemJson) {
            $this->updatedCount = $this->updatedCount + 1;
            $this->api->update('items', $itemJson['id'], $itemJson);
            $this->api->update('omekaimport_records', $importRecordId, $importRecordUpdateJson);
            $em->detach($unitOfWork->getIdentityMap()['Omeka\Entity\Resource'][$itemJson['id']]);
            $em->detach($unitOfWork->getIdentityMap()['Omeka2Importer\Entity\OmekaimportRecord'][$importRecordId]);
        }
    }

    protected function buildImportRecordJson($remoteId, $resourceReference, $type = 'item')
    {
        $recordJson = array('o:job' => array('o:id' => $this->job->getId()),
                            'endpoint' => $this->endpoint,
                            'remote_type' => $type,
                            'remote_id' => $remoteId,
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
        $resourceJson['o:item_set'] = array();
        
        if (isset($importData['collection'])) {
            $omekaCollectionId = $importData['collection']['id'];
            if (isset($this->collectionItemSetMap[$omekaCollectionId])) {
                $resourceJson['o:item_set'][] = array('o:id' => $this->collectionItemSetMap[$omekaCollectionId]);
            }
        }
        
        if (isset($options['itemSet'])) {
            if (is_array($options['itemSet'])) {
                foreach($options['itemSet'] as $itemSetId) {
                    $resourceJson['o:item_set'][] = array('o:id' => $itemSetId);
                }
            } else {
                $resourceJson['o:item_set'][] = array('o:id' => $options['itemSet']);
            }
        }
        
        $resourceClassId = null;
        if (isset($importData['item_type'])) {
            $itemTypeName = $importData['item_type']['name'];
            $itemTypeId = $importData['item_type']['id'];
            if (array_key_exists($itemTypeId, $this->typeMap)) {
                $resourceClassId = $this->typeMap[$itemTypeId];
            }
        }
        $resourceJson['o:resource_class'] = array('o:id' => $resourceClassId);
        $resourceJson = array_merge($resourceJson, $this->buildPropertyJson($importData));
        $mediaJson = $this->buildMediaJson($importData);
        $mediaJson = $this->buildHtmlMediaJson($importData, $mediaJson);
        $resourceJson = array_merge($resourceJson, $mediaJson);

        return $resourceJson;
    }

    protected function buildHtmlMediaJson($importData, $mediaJson)
    {
        // @TODO this imagines adding on to the o:media array.
        //probably rework these two methods to play better together
        //by generating the nested array for both, and tacking
        //on to o:media at a higher level
        $itemId = $importData['id'];
        foreach ($importData['element_texts'] as $elTextData) {
            if (array_key_exists($elTextData['element']['id'], $this->htmlElementMap)) {
                $htmlJson = array(
                        'o:ingester' => 'html',
                        'data' => array(
                            'html' => $elTextData['text'],
                            'dcterms:title' => array(
                                'property_id' => $this->dctermsTitleId,
                                '@value' => $elTextData['element']['name'],
                            ),
                        ),
                );
                $mediaJson['o:media'][] = $htmlJson;
            }
        }

        return $mediaJson;
    }

    protected function buildMediaJson($importData)
    {
        //another query to get the filesData from the importData
        $itemId = $importData['id'];
        $response = $this->client->files->get(array('item' => $itemId));
        $filesData = json_decode($response->getBody(), true);
        $mediaJson = array('o:media' => array());
        foreach ($filesData as $fileData) {
            $fileJson = array(
                'o:ingester' => 'url',
                'o:source' => $fileData['file_urls']['original'],
                'ingest_url' => $fileData['file_urls']['original'],
            );
            $fileJson = array_merge($fileJson, $this->buildPropertyJson($fileData));
            $mediaJson['o:media'][] = $fileJson;
        }

        return $mediaJson;
    }

    protected function buildPropertyJson($importData)
    {
        $propertyJson = array();
        foreach ($importData['element_texts'] as $elTextData) {
            $value = strip_tags($elTextData['text']);
            $elementSetId = $elTextData['element_set']['id'];
            $elementId = $elTextData['element']['id'];

            //elementMap has keys of the element id, and array of propertyIds it's mapped to
            if (array_key_exists($elementId, $this->elementMap)) {
                //loop through all the mappings for that element and build json
                foreach ($this->elementMap[$elementId] as $propertyId) {
                    $propertyJson[$propertyId][] = array(
                            '@value' => $value,
                            'property_id' => $propertyId,
                            'type' => 'literal',
                            );
                }
            }
        }

        return $propertyJson;
    }

    protected function importRecord($remoteId, $remoteType = 'item')
    {
        //see if the item has already been imported
        $response = $this->api->search('omekaimport_records',
                                array('remote_id' => $remoteId,
                                      'remote_type' => $remoteType,
                                      'endpoint' => $this->endpoint,
                                       ));
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
