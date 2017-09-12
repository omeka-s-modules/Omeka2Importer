<?php

namespace Omeka2Importer;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Job;
use Zend\ServiceManager\ServiceLocatorInterface;
use Composer\Semver\Comparator;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec('CREATE TABLE omekaimport_record (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, item_id INT DEFAULT NULL, item_set_id INT DEFAULT NULL, remote_type VARCHAR(255) NOT NULL, remote_id INT NOT NULL, endpoint VARCHAR(255) NOT NULL, INDEX IDX_3185E9B1BE04EA9 (job_id), UNIQUE INDEX UNIQ_3185E9B1126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $connection->exec('ALTER TABLE omekaimport_record ADD CONSTRAINT FK_3185E9B1BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);');
        $connection->exec('ALTER TABLE omekaimport_record ADD CONSTRAINT FK_3185E9B1126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
        $connection->exec("ALTER TABLE omekaimport_record ADD CONSTRAINT FK_3185E9B1960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE CASCADE;");

        $connection->exec('CREATE TABLE omekaimport_import (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, undo_job_id INT DEFAULT NULL, added_count INT NOT NULL, updated_count INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_37FFB83DBE04EA9 (job_id), UNIQUE INDEX UNIQ_37FFB83D4C276F75 (undo_job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $connection->exec('ALTER TABLE omekaimport_import ADD CONSTRAINT FK_37FFB83DBE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);');
        $connection->exec('ALTER TABLE omekaimport_import ADD CONSTRAINT FK_37FFB83D4C276F75 FOREIGN KEY (undo_job_id) REFERENCES job (id);');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $connection->exec('ALTER TABLE omekaimport_record DROP FOREIGN KEY FK_3185E9B1BE04EA9;');
        $connection->exec('ALTER TABLE omekaimport_record DROP FOREIGN KEY FK_3185E9B1126F525E;');
        $connection->exec('ALTER TABLE omekaimport_record DROP FOREIGN KEY FK_3185E9B1960278D7;');
        $connection->exec('DROP TABLE omekaimport_record');

        $connection->exec('ALTER TABLE omekaimport_import DROP FOREIGN KEY FK_37FFB83DBE04EA9');
        $connection->exec('ALTER TABLE omekaimport_import DROP FOREIGN KEY FK_37FFB83D4C276F75');
        $connection->exec('DROP TABLE omekaimport_import');
    }

    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator
    ) {
        if (Comparator::lessThan($oldVersion, '1.0.0-beta')) {
            $connection = $serviceLocator->get('Omeka\Connection');
            $connection->exec("ALTER TABLE omekaimport_record DROP INDEX FK_3185E9B1960278D7, ADD UNIQUE INDEX UNIQ_3185E9B1960278D7 (item_set_id);");
            $connection->exec("ALTER TABLE omekaimport_record DROP FOREIGN KEY FK_3185E9B1960278D7;");
            $connection->exec("ALTER TABLE omekaimport_record ADD CONSTRAINT FK_3185E9B1960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE CASCADE;");
        }
    }
}
