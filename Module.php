<?php

namespace Omeka2Importer;

use Omeka\Module\AbstractModule;
use Omeka\Entity\Job;
use Omeka2Importer\Form\ConfigForm;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Composer\Semver\Comparator;

class Module extends AbstractModule
{
    /**
     * @var array Cache of vocabulary members (classes and properties).
     */
    protected $vocabMembers;

    /**
     * @var array Cache of resource templates .
     */
    protected $resourceTemplates;

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

    public function getConfigForm(PhpRenderer $renderer)
    {
        $form = new ConfigForm;
        $form->init();
        return $renderer->formCollection($form, false);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $form = new ConfigForm;
        $form->init();
        $form->setData($controller->params()->fromPost());
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }
        $formData = $form->getData();
        if ($formData['import_classic']) {
            $this->importClassicDataModel();
        }
        return true;
    }

    /**
     * Get vocabulary members (classes and properties).
     *
     * @return array
     */
    public function getVocabMembers()
    {
        if (isset($this->vocabMembers)) {
            return $this->vocabMembers;
        }
        $services = $services = $this->getServiceLocator();
        $conn = $services->get('Omeka\Connection');
        // Cache vocab members.
        $vocabMembers = [];
        foreach (['resource_class', 'property'] as $member) {
            $sql = 'SELECT m.id, m.local_name, v.prefix FROM %s m JOIN vocabulary v ON m.vocabulary_id = v.id';
            $stmt = $conn->query(sprintf($sql, $member));
            $vocabMembers[$member] = [];
            foreach ($stmt as $row) {
                $vocabMembers[$member][sprintf('%s:%s', $row['prefix'], $row['local_name'])] = $row['id'];
            }
        }
        return $this->vocabMembers = $vocabMembers;
    }

    /**
     * Get resource templates.
     *
     * @return array
     */
    public function getResourceTemplates()
    {
        if (isset($this->resourceTemplates)) {
            return $this->resourceTemplates;
        }
        $services = $services = $this->getServiceLocator();
        $conn = $services->get('Omeka\Connection');
        $sql = 'SELECT rt.label FROM resource_template rt';
        $stmt = $conn->query($sql);
        $resourceTemplates = [];
        foreach ($stmt as $row) {
            $resourceTemplates[] = $row['label'];
        }
        return $this->resourceTemplates = $resourceTemplates;
    }

    public function importClassicDataModel()
    {
        $services = $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $importer = $services->get('Omeka\RdfImporter');

        $response = $api->search('vocabularies', [
            'namespace_uri' => 'http://omeka.org/s/vocabs/oc#',
            'limit' => 0,
        ]);
        if (0 === $response->getTotalResults()) {
            // Import the "Omeka Classic" vocabulary.
            $importer->import(
                'file',
                [
                    'o:namespace_uri' => 'http://omeka.org/s/vocabs/oc#',
                    'o:prefix' => 'oc',
                    'o:label' => 'Omeka Classic',
                    'o:comment' =>  null,
                ],
                [
                    'file' => __DIR__ . '/vocabs/oc.ttl',
                    'format' => 'turtle',
                ]
            );
        }

        $templates = array_filter([
            $this->buildTemplate('Text', 'dctype:Text', ['oc:text', 'oc:originalFormat']),
            $this->buildTemplate('Moving Image', 'dctype:MovingImage', ['oc:transcription', 'oc:originalFormat', 'oc:duration', 'oc:compression', 'bibo:producer', 'bibo:director']),
            $this->buildTemplate('Oral History', 'oc:OralHistory', ['bibo:interviewer', 'bibo:interviewee', 'oc:location', 'oc:transcription', 'oc:originalFormat', 'oc:duration', 'oc:bitRateFrequency', 'oc:timeSummary']),
            $this->buildTemplate('Sound', 'dctype:Sound', ['oc:transcription', 'oc:originalFormat', 'oc:duration', 'oc:bitRateFrequency']),
            $this->buildTemplate('Still Image', 'dctype:StillImage', ['oc:originalFormat', 'oc:physicalDimensions']),
            $this->buildTemplate('Website', 'bibo:Website', ['oc:localUrl']),
            $this->buildTemplate('Event', 'dctype:Event', ['oc:duration', 'oc:eventType', 'oc:participants']),
            $this->buildTemplate('Email', 'bibo:Email', ['oc:emailBody', 'oc:subjectLine', 'oc:from', 'oc:to', 'oc:cc', 'oc:bcc', 'oc:numberOfAttachments']),
            $this->buildTemplate('Lesson Plan', 'oc:LessonPlan', ['oc:duration', 'oc:standards', 'oc:objectives', 'oc:materials', 'oc:lessonPlanText']),
            $this->buildTemplate('Hyperlink', 'oc:Hyperlink', ['bibo:uri']),
            $this->buildTemplate('Person', 'foaf:Person', ['foaf:birthday', 'oc:birthplace', 'oc:deathDate', 'oc:occupation', 'oc:biographicalText', 'oc:bibliography']),
            $this->buildTemplate('Interactive Resource', 'dctype:InteractiveResource', []),
            $this->buildTemplate('Dataset', 'dctype:Dataset', []),
            $this->buildTemplate('Physical Object', 'dctype:PhysicalObject', []),
            $this->buildTemplate('Service', 'dctype:Service', []),
            $this->buildTemplate('Software', 'dctype:Software', []),
        ]);
        if ($templates) {
            // Create the "Omeka Classic" resource templates.
            $response = $api->batchCreate('resource_templates', $templates);
        }
    }

    protected function buildTemplate($label, $classTerm, array $propertyTerms)
    {
        if (in_array($label, $this->getResourceTemplates())) {
            // A template using this label already exists.
            return null;
        }
        $vocabMembers = $this->getVocabMembers();
        $template = [
            'o:label' => $label,
            // Every template will contain "Dublin Core Elements" properties.
            'o:resource_template_property' => [
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:title']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:description']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:contributor']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:coverage']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:creator']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:date']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:format']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:identifier']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:language']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:publisher']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:relation']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:rights']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:source']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:subject']]],
                ['o:property' => ['o:id' => $vocabMembers['property']['dcterms:type']]],
            ],
        ];
        if (isset($vocabMembers['resource_class'][$classTerm])) {
            // Set the class if it exists.
            $template['o:resource_class'] = ['o:id' => $vocabMembers['resource_class'][$classTerm]];
        }
        foreach ($propertyTerms as $propertyTerm) {
            if (isset($vocabMembers['property'][$propertyTerm])) {
                // Set the property if it exists.
                $template['o:resource_template_property'][] = [
                    'o:property' => ['o:id' => $vocabMembers['property'][$propertyTerm]],
                ];
            }
        }
        return $template;
    }
}
