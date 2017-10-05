<?php

namespace Omeka2Importer\Importer;

abstract class AbstractImporter implements ImporterInterface
{
    public $serviceLocator;

    public $client;

    public function __construct($client, $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        $this->client = $client;
    }

    /**
     * Must return the modified $resourceJson
     * {@inheritDoc}
     * @see \Omeka2Importer\Importer\ImporterInterface::import()
     */
    abstract public function import($itemData, $resourceJson);

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
