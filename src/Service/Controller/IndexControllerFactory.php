<?php

namespace Omeka2Importer\Service\Controller;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Omeka2Importer\Controller\IndexController;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(containerInterface $services, $requestedName, array $options = null)
    {
        $client = $services->get('Omeka2Importer\Omeka2Client');
        $indexController = new IndexController($client);

        return $indexController;
    }
}
