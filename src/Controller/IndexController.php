<?php
namespace Omeka2Importer\Controller;

use Omeka2Importer\Form\ImportForm;
use Omeka2Importer\Form\MappingForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $form = new ImportForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                $job = $dispatcher->dispatch('Omeka2Importer\Job\Import', $data);
                //the Omeka2Import record is created in the job, so it doesn't
                //happen until the job is done
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
    
    public function fetchMappingDataAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $client = $this->getServiceLocator()->get('Omeka2Importer\Omeka2Client');
        $data = $this->params()->fromQuery();
        $endpoint = rtrim($data['endpoint'], '/');
        $client->setApiBaseUrl($endpoint);
        $elementsData = array();
        $elementSetsResponse = $client->element_sets->get();
        $elementSets = json_decode($elementSetsResponse->getBody(), true);
        foreach($elementSets as $elementSet) {
            $elementsResponse = $client->elements->get(array('element_set' => $elementSet['id']));
            $elements = json_decode($elementsResponse->getBody(), true);
            $elementsData[$elementSet['name']] = $elements;
        }
        $elementDefaultMap = $this->buildElementDefaultMap($elementsData);
        $itemTypesResponse = $client->item_types->get();
        $itemTypes = json_decode($itemTypesResponse->getBody(), true);
        $typeDefaultMap = $this->buildTypeDefaultMap($itemTypes);
        $view->setVariable('elementDefaultMap', $elementDefaultMap);
        $view->setVariable('typeDefaultMap', $typeDefaultMap);
        $view->setVariable('elementsData', $elementsData);
        $view->setVariable('itemTypes', $itemTypes);
        return $view;
    }

    protected function undoJob($jobId) {
        $response = $this->api()->search('omekaimport_imports', array('job_id' => $jobId));
        if ($response->isError()) {

        }
        $fedoraImport = $response->getContent()[0];
        $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
        $job = $dispatcher->dispatch('Omeka2Importer\Job\Undo', array('jobId' => $jobId));
        $response = $this->api()->update('omekaimport_imports', 
                    $fedoraImport->id(), 
                    array(
                        'o:undo_job' => array('o:id' => $job->getId() )
                    )
                );
        if ($response->isError()) {
        }
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
}