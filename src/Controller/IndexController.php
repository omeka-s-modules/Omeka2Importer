<?php

namespace Omeka2Importer\Controller;

use Omeka2Importer\Form\ImportForm;
use Omeka2Importer\Form\MappingForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function indexAction()
    {
        $view = new ViewModel();
        $form = $this->getForm(ImportForm::class);
        $form->setAttribute('action', 'omeka2importer/map-elements');
        $form->setAttribute('method', 'GET');
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch('Omeka2Importer\Job\Import', $data);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID '.$job->getId()); // @translate
                $view->setVariable('job', $job);
            } else {
                $this->messenger()->addError('There was an error during validation'); // @translate
            }
        }

        $view->setVariable('form', $form);

        return $view;
    }

    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $undoJobIds = [];
            foreach ($data['jobs'] as $jobId) {
                $undoJob = $this->undoJob($jobId);
                $undoJobIds[] = $undoJob->getId();
            }
            $this->messenger()->addSuccess('Undo in progress in the following jobs: ' . implode(', ', $undoJobIds)); // @translate
            return $this->redirect()->refresh();
        }
        $view = new ViewModel();
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + [
            'page' => $page,
            'sort_by' => $this->params()->fromQuery('sort_by', 'id'),
            'sort_order' => $this->params()->fromQuery('sort_order', 'desc'),
        ];
        $response = $this->api()->search('omekaimport_imports', $query);
        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('imports', $response->getContent());

        return $view;
    }

    public function mapElementsAction()
    {
        $view = new ViewModel();
        $form = $this->getForm(MappingForm::class);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost(null, []);
            $form->setData($data);
            if ($form->isValid()) {
                $job = $this->jobDispatcher()->dispatch('Omeka2Importer\Job\Import', $data);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID '.$job->getId()); // @translate
                return $this->redirect()->toRoute('admin/omeka2importer/past-imports', ['action' => 'browse'], true);
                $view->setVariable('job', $job);
            } else {
                $this->messenger()->addError('There was an error during validation'); // @translate
            }
        }

        $data = $this->params()->fromQuery();
        $endpoint = rtrim($data['endpoint'], '/');
        $this->client->setApiBaseUrl($endpoint);

        //first, check if it looks like a valid Omeka 2 endpoint
        $testResponse = $this->client->resources->get();
        if ($testResponse->getStatusCode() != 200) {
            //throw new \Exception('no omeka for you!');
            $this->messenger()->addError(sprintf('The endpoint %s is not a valid Omeka 2 endpoint.', $endpoint)); // @translate

            return $this->redirect()->toRoute(
                'admin/omeka2importer',
                ['action' => 'index'],
                true
            );
        }

        //gather up all the element sets
        $elementSetsData = [];
        $page = 1;
        do {
            $elementSetsResponse = $this->client->element_sets->get(['page' => $page]);
            $elementSets = json_decode($elementSetsResponse->getBody(), true);
            $elementSetsData = array_merge($elementSetsData, $elementSets);
            ++$page;
        } while ($this->hasNextPage($elementSetsResponse));

        $elementsData = $this->fetchElementsMappingData($endpoint);
        $itemTypesData = $this->fetchItemTypesMappingData($endpoint);

        $elementDefaultMap = $this->buildElementDefaultMap($elementsData);
        $typeDefaultMap = $this->buildTypeDefaultMap($itemTypesData);
        $templateDefaultMap = $this->buildTemplateDefaultMap($itemTypesData);

        $view->setVariable('elementDefaultMap', $elementDefaultMap);
        $view->setVariable('typeDefaultMap', $typeDefaultMap);
        $view->setVariable('templateDefaultMap', $templateDefaultMap);
        $view->setVariable('elementsData', $elementsData);
        $view->setVariable('itemTypes', $itemTypesData);
        $view->setVariable('endpoint', $endpoint);

        return $view;
    }

    protected function fetchElementsMappingData($endpoint)
    {
        $endpoint = rtrim($endpoint, '/');

        $this->client->setApiBaseUrl($endpoint);

        //gather up all the element sets
        $elementSetsData = [];
        $page = 1;
        do {
            $elementSetsResponse = $this->client->element_sets->get(['page' => $page]);
            $elementSets = json_decode($elementSetsResponse->getBody(), true);
            $elementSetsData = array_merge($elementSetsData, $elementSets);
            ++$page;
        } while ($this->hasNextPage($elementSetsResponse));

        $elementsData = [];
        foreach ($elementSetsData as $elementSet) {
            $page = 1;
            $elementSetElements = [];
            do {
                $elementsResponse = $this->client->elements->get(['element_set' => $elementSet['id'], 'page' => $page]);
                $elements = json_decode($elementsResponse->getBody(), true);
                $elementSetElements = array_merge($elementSetElements, $elements);
                ++$page;
            } while ($this->hasNextPage($elementsResponse));
            $elementsData[$elementSet['name']] = $elementSetElements;
        }

        return $elementsData;
    }

    protected function fetchItemTypesMappingData($endpoint)
    {
        $endpoint = rtrim($endpoint, '/');

        $this->client->setApiBaseUrl($endpoint);

        $itemTypesData = [];
        $page = 1;
        do {
            $itemTypesResponse = $this->client->item_types->get(['page' => $page]);
            $itemTypes = json_decode($itemTypesResponse->getBody(), true);
            $itemTypesData = array_merge($itemTypesData, $itemTypes);
            ++$page;
        } while ($this->hasNextPage($itemTypesResponse));

        return $itemTypesData;
    }

    protected function undoJob($jobId)
    {
        $response = $this->api()->search('omekaimport_imports', ['job_id' => $jobId]);
        $omekaImport = $response->getContent()[0];
        $job = $this->jobDispatcher()->dispatch('Omeka2Importer\Job\Undo', ['jobId' => $jobId]);
        $response = $this->api()->update('omekaimport_imports',
                    $omekaImport->id(),
                    [
                        'o:undo_job' => ['o:id' => $job->getId()],
                    ]
                );
        return $job;
    }

    protected function buildElementDefaultMap($elementsData)
    {
        include 'item_type_maps.php';

        $propertiesByLabel = $this->getPropertiesByLabel();

        $elementMap = [];
        foreach ($elementsData as $elementSet => $elements) {
            foreach ($elements as $elementData) {
                $propertyId = false;
                $elementName = $elementData['name'];
                if ($elementSet == 'Dublin Core') {
                    if (array_key_exists($elementName, $propertiesByLabel)) {
                        $property = $propertiesByLabel[$elementName];
                        $term = $property->term();
                        $propertyId = $property->id();
                        $propertyLabel = $property->label();
                    }
                } else {
                    if (array_key_exists($elementName, $itemTypeElementMap)) {
                        $term = $itemTypeElementMap[$elementName];
                        $propertyResponse = $this->api()->search('properties', ['term' => $term]);
                        if (!empty($propertyResponse->getContent())) {
                            $property = $propertyResponse->getContent()[0];
                            $propertyId = $property->id();
                            $propertyLabel = $property->label();
                        }
                    }
                }
                if ($propertyId) {
                    $elementMap[$elementSet][$elementName] =
                        ['propertyId' => $propertyId, 'term' => $term, 'propertyLabel' => $propertyLabel];
                }
            }
        }

        return $elementMap;
    }

    protected function buildTypeDefaultMap($itemTypes)
    {
        include 'item_type_maps.php';
        $typeMap = [];
        foreach ($itemTypes as $type) {
            if (array_key_exists($type['name'], $itemTypeMap)) {
                $classResponse = $this->api()->search('resource_classes', ['term' => $itemTypeMap[$type['name']]]);
                if (!empty($classResponse->getContent())) {
                    $class = $classResponse->getContent()[0];
                    $classId = $class->id();
                    $classLabel = $class->label();
                    $typeMap[$type['name']] =
                        ['classId' => $classId, 'classLabel' => $classLabel];
                }
            }
        }

        return $typeMap;
    }

    protected function buildTemplateDefaultMap($itemTypes)
    {
        $templateMap = [];
        foreach ($itemTypes as $type) {
            $template = $this->api()->search(
                'resource_templates',
                ['label' => $type['name']]
            )->getContent();
            if ($template) {
                $templateMap[$type['name']] = [
                    'templateId' => $template[0]->id(),
                    'templateLabel' => $template[0]->label(),
                ];
            }
        }
        return $templateMap;
    }

    protected function hasNextPage($response)
    {
        $headers = $response->getHeaders();
        $linksHeaders = $response->getHeaders()->get('Link')->toString();

        return strpos($linksHeaders, 'rel="next"');
    }

    protected function getPropertiesByLabel()
    {
        $properties = $this->api()->search('properties', [
            'vocabulary_prefix' => 'dcterms',
        ])->getContent();

        $propertiesByLabel = [];
        foreach ($properties as $property) {
            $propertiesByLabel[$property->label()] = $property;
        }

        return $propertiesByLabel;
    }
}
