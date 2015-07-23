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

    protected $itemSetId;

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
        $this->importItems();
    }

    protected function importItems()
    {
        $page = 1;
        do {
            $response = $this->client->items->get(null, array('page' => $page));
            $itemsData = json_decode($response->getBody(), true);
            foreach($itemsData as $itemData) {
                $itemJson = array();
                $itemJson = $this->buildResourceJson($itemData);
                $itemJson = array_merge($itemJson, $this->buildMediaJson($itemData));
                $response = $this->api->create('items', $itemJson);
                $itemId = $response->getContent()->id();

                $importItemEntityJson = array(
                                'o:job'         => array('o:id' => $this->job->getId()),
                                'o:item'        => array('o:id' => $itemId),
                                'endpoint'      => $this->endpoint,
                                'last_modified' => $itemData['modified']
                              );
                $this->api->create('omeka2items', $importItemEntityJson);
            }
            $page++;
        } while ($this->hasNextPage($response));
    }

    protected function buildResourceJson($importData, $hasAsset = false)
    {
        $resourceJson = array();
        $resourceJson = array_merge($resourceJson, $this->buildPropertyJson($importData));
        if($hasAsset) {
            $resourceJson = array_merge($resourceJson, $this->buildAssetJson());
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

    protected function hasNextPage($response)
    {
        $headers = $response->getHeaders();
        $linksHeaders = $response->getHeaders()->get('Link')->toString();
        return strpos($linksHeaders, 'rel="next"');
    }
}