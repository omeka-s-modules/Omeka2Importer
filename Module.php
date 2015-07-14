<?php
namespace Omeka2Importer;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\Mvc\Controller\AbstractController;
use Zend\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        /*
        $connection->exec("");
        $connection->exec("");
        $connection->exec("");
        $connection->exec("");
        */
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        /*
        $connection->exec("");
        $connection->exec("");
        $connection->exec('');
        $connection->exec('');
        */
    }
}