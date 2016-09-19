<?php

namespace Omeka2Importer\Service\Controller;

use Omeka2Importer\Controller\IndexController;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
<<<<<<< HEAD
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $serviceLocator = $serviceLocator->getServiceLocator();
        $client = $serviceLocator->get('Omeka2Importer\Omeka2Client');
        $indexController = new IndexController($client);

=======
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {   
        $client = $container->get('Omeka2Importer\Omeka2Client');
        $indexController = new IndexController($client);
>>>>>>> master
        return $indexController;
    }
}
