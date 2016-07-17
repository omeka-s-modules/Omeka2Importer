<?php
namespace Omeka2Importer\Controller;

use Omeka2Importer\Form\ImportForm;
use Omeka2Importer\Form\MappingForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    
    protected $logger;
    
    protected $client;
    
    protected $jobDispatcher;
    
    public function __construct($logger, $jobDispatcher, $client)
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->jobDispatcher = $jobDispatcher;
    }
    
    public function indexAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(ImportForm::class);
        $form->setAttribute('action', 'omeka2importer/map-elements');
        $form->setAttribute('method', 'GET');
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $job = $this->jobDispatcher->dispatch('Omeka2Importer\Job\Import', $data);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
                $view->setVariable('job', $job);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('form', $form);

        return $view;
    }

    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            foreach ($data['jobs'] as $jobId) {
                $this->undoJob($jobId);
            }
        }
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array(
            'page'       => $page,
            'sort_by'    => $this->params()->fromQuery('sort_by', 'id'),
            'sort_order' => $this->params()->fromQuery('sort_order', 'desc')
        );
        $response = $this->api()->search('omekaimport_imports', $query);
        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('imports', $response->getContent());
        return $view;
    }
    
    public function mapElementsAction()
    {

        
        $view = new ViewModel;
        $form = $this->getForm(MappingForm::class);
        $view->setVariable('form', $form);
        
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost(null, array());
            $form->setData($data);
            if ($form->isValid()) {
                $job = $this->jobDispatcher->dispatch('Omeka2Importer\Job\Import', $data);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
                $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
                $view->setVariable('job', $job);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        
        
        $data = $this->params()->fromQuery();
        $endpoint = rtrim($data['endpoint'], '/');
        $this->client->setApiBaseUrl($endpoint);
        
        //first, check if it looks like a valid Omeka 2 endpoint
        $testResponse = $this->client->resources->get();
        if($testResponse->getStatusCode() != 200) {
            //throw new \Exception('no omeka for you!');
            $this->messenger()->addError(sprintf('The endpoint %s is not a valid Omeka 2 endpoint.', $endpoint));
            return $this->redirect()->toRoute(
                'admin/omeka2importer',
                ['action' => 'index'],
                true
            );
        }

        //gather up all the element sets
        $elementSetsData = array();
        $page = 1;
        do {
            $elementSetsResponse = $this->client->element_sets->get(array('page' => $page));
            $elementSets = json_decode($elementSetsResponse->getBody(), true);
            $elementSetsData = array_merge($elementSetsData, $elementSets);
            $page++;
        } while ($this->hasNextPage($elementSetsResponse));
        
        
        $elementsData = $this->fetchElementsMappingData($endpoint);
        $itemTypesData = $this->fetchItemTypesMappingData($endpoint);

        $elementDefaultMap = $this->buildElementDefaultMap($elementsData);
        $typeDefaultMap = $this->buildTypeDefaultMap($itemTypesData);
        
        $view->setVariable('elementDefaultMap', $elementDefaultMap);
        $view->setVariable('typeDefaultMap', $typeDefaultMap);
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
        $elementSetsData = array();
        $page = 1;
        do {
            $elementSetsResponse = $this->client->element_sets->get(array('page' => $page));
            $elementSets = json_decode($elementSetsResponse->getBody(), true);
            $elementSetsData = array_merge($elementSetsData, $elementSets);
            $page++;
        } while ($this->hasNextPage($elementSetsResponse));
        
        
        $elementsData = array();
        foreach($elementSetsData as $elementSet) {
            $page = 1;
            $elementSetElements = array();
            do {
                $elementsResponse = $this->client->elements->get(array('element_set' => $elementSet['id'], 'page' => $page));
                $elements = json_decode($elementsResponse->getBody(), true);
                $elementSetElements = array_merge($elementSetElements, $elements);
                $page++;
            } while ($this->hasNextPage($elementsResponse));
            $elementsData[$elementSet['name']] = $elementSetElements;
        }
        return $elementsData;
    }
    
    protected function fetchItemTypesMappingData($endpoint)
    {
        $endpoint = rtrim($endpoint, '/');
        
        $this->client->setApiBaseUrl($endpoint);
        
        $itemTypesData = array();
        $page = 1;
        do {
            $itemTypesResponse = $this->client->item_types->get(array('page' => $page));
            $itemTypes = json_decode($itemTypesResponse->getBody(), true);
            $itemTypesData = array_merge($itemTypesData, $itemTypes);
            $page++;
        } while ($this->hasNextPage($itemTypesResponse));
        
        return $itemTypesData;
    }

    protected function undoJob($jobId) {
        $response = $this->api()->search('omekaimport_imports', array('job_id' => $jobId));
        $omekaImport = $response->getContent()[0];
        $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
        $job = $this->jobDispatcher->dispatch('Omeka2Importer\Job\Undo', array('jobId' => $jobId));
        $response = $this->api()->update('omekaimport_imports', 
                    $omekaImport->id(), 
                    array(
                        'o:undo_job' => array('o:id' => $job->getId() )
                    )
                );
    }

    protected function buildElementDefaultMap($elementsData)
    {
        include('item_type_maps.php');
        $elementMap = array();
        foreach ($elementsData as $elementSet => $elements) {
            foreach ($elements as $elementData) {
                $propertyId = false;
                $elementName = $elementData['name'];
                if ($elementSet == 'Dublin Core') {
                    $term = "dcterms:" . lcfirst(str_replace(" ", "", $elementName));
                    $propertyResponse = $this->api()->search('properties', array('term' => $term));
                    if (!empty($propertyResponse->getContent())) {
                        $property = $propertyResponse->getContent()[0];
                        $propertyId = $property->id();
                        $propertyLabel = $property->label();
                    }

                } else {
                    if (array_key_exists($elementName, $itemTypeElementMap)) {
                        $term = $itemTypeElementMap[$elementName];
                        $propertyResponse = $this->api()->search('properties', array('term' => $term));
                        if (!empty($propertyResponse->getContent())) {
                            $property = $propertyResponse->getContent()[0];
                            $propertyId = $property->id();
                            $propertyLabel = $property->label();
                        }
                    }
                }
                if ($propertyId) {
                    $elementMap[$elementSet][$elementName] = 
                        array('propertyId' => $propertyId, 'term' => $term, 'propertyLabel' => $propertyLabel);
                }
            }
        }
        return $elementMap;
    }
    
    protected function buildTypeDefaultMap($itemTypes)
    {
        include('item_type_maps.php');
        $typeMap = array();
        foreach ($itemTypes as $type) {
            if(array_key_exists($type['name'], $itemTypeMap)) {
                $classResponse = $this->api()->search('resource_classes', array('term' => $itemTypeMap[$type['name']]));
                if (!empty($classResponse->getContent())) {
                    $class = $classResponse->getContent()[0];
                    $classId = $class->id();
                    $classLabel = $class->label();
                    $typeMap[$type['name']] = 
                        array('classId' => $classId, 'classLabel' => $classLabel);
                }
            }
        }
        return $typeMap;
    }
    
    protected function hasNextPage($response)
    {
        $headers = $response->getHeaders();
        $linksHeaders = $response->getHeaders()->get('Link')->toString();
        return strpos($linksHeaders, 'rel="next"');
    }
}