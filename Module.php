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

        $connection->exec("CREATE TABLE omeka2item (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, item_id INT NOT NULL, remote_item_id INT NOT NULL, uri VARCHAR(255) NOT NULL, last_modified DATETIME NOT NULL, INDEX IDX_59E62AA3BE04EA9 (job_id), UNIQUE INDEX UNIQ_59E62AA3126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;");
        $connection->exec("ALTER TABLE omeka2item ADD CONSTRAINT FK_59E62AA3BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);");
        $connection->exec("ALTER TABLE omeka2item ADD CONSTRAINT FK_59E62AA3126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;");
//        $connection->exec("");
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