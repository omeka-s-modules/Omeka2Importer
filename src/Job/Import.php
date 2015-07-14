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
        $this->endpoint = rtrim($this->getArg('endpoint'), '/');
        $this->client = $this->getServiceLocator()->get('Omeka2Importer\Omeka2Client');
        $this->client->setApiBaseUrl($this->endpoint);
        $this->client->setKey($this->getArg('key'));
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->prepareTermIdMap();
        
        //$this->importCollections();
        $this->importItems();
        
    }

    protected function importItems()
    {
        $response = $this->client->items->get();
        $itemsData = json_decode($response->getBody(), true);
        foreach($itemsData as $itemData) {
            $itemJson = array();
            $itemJson = $this->processElementTexts($itemData, $itemJson);
            $this->api->create('items', $itemJson);
        }
    }
    
    protected function processElementTexts($itemData, $entityJson)
    {
        foreach($itemData['element_texts'] as $elTextData)
        {
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
                    $entityJson[$term][] = array(
                            '@value'      => $value,
                            'property_id' => $propertyId
                            );
                }
            }
        }
        return $entityJson;
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
}
