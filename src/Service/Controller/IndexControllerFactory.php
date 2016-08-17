<?php

namespace Omeka2Importer\Service\Controller;

use Omeka2Importer\Controller\IndexController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $serviceLocator = $serviceLocator->getServiceLocator();
        $client = $serviceLocator->get('Omeka2Importer\Omeka2Client');
        $indexController = new IndexController($client);

        return $indexController;
    }
}
