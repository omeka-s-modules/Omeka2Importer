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
    
    public function mappingAction()
    {
        $client = $this->getServiceLocator()->get('Omeka2Importer\Omeka2Client');
        $fakeElementData = json_decode('[{"id":37,"url":"http:\/\/localhost\/Omeka\/api\/elements\/37","order":9,"name":"Contributor","description":"An entity responsible for making contributions to the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":257,"url":"http:\/\/localhost\/Omeka\/api\/elements\/257","order":17,"name":"Copyright","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":38,"url":"http:\/\/localhost\/Omeka\/api\/elements\/38","order":15,"name":"Coverage","description":"The spatial or temporal topic of the resource, the spatial applicability of the resource, or the jurisdiction under which the resource is relevant","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":39,"url":"http:\/\/localhost\/Omeka\/api\/elements\/39","order":5,"name":"Creator","description":"An entity primarily responsible for making the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":40,"url":"http:\/\/localhost\/Omeka\/api\/elements\/40","order":8,"name":"Date","description":"A point or period of time associated with an event in the lifecycle of the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":253,"url":"http:\/\/localhost\/Omeka\/api\/elements\/253","order":21,"name":"Date Issued","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":41,"url":"http:\/\/localhost\/Omeka\/api\/elements\/41","order":4,"name":"Description","description":"An account of the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":42,"url":"http:\/\/localhost\/Omeka\/api\/elements\/42","order":12,"name":"Format","description":"The file format, physical medium, or dimensions of the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":43,"url":"http:\/\/localhost\/Omeka\/api\/elements\/43","order":14,"name":"Identifier","description":"An unambiguous reference to the resource within a given context","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":259,"url":"http:\/\/localhost\/Omeka\/api\/elements\/259","order":19,"name":"Is Part of Series","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":44,"url":"http:\/\/localhost\/Omeka\/api\/elements\/44","order":2,"name":"Language","description":"A language of the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":258,"url":"http:\/\/localhost\/Omeka\/api\/elements\/258","order":18,"name":"Link to Copyright License","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":255,"url":"http:\/\/localhost\/Omeka\/api\/elements\/255","order":23,"name":"Physical Source of the Collection","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":45,"url":"http:\/\/localhost\/Omeka\/api\/elements\/45","order":7,"name":"Publisher","description":"An entity responsible for making the resource available","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":256,"url":"http:\/\/localhost\/Omeka\/api\/elements\/256","order":16,"name":"Publisher of Digital Copy","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":254,"url":"http:\/\/localhost\/Omeka\/api\/elements\/254","order":22,"name":"Publishing Location","description":"","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":46,"url":"http:\/\/localhost\/Omeka\/api\/elements\/46","order":11,"name":"Relation","description":"A related resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":47,"url":"http:\/\/localhost\/Omeka\/api\/elements\/47","order":10,"name":"Rights","description":"Information about rights held in and over the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":48,"url":"http:\/\/localhost\/Omeka\/api\/elements\/48","order":6,"name":"Source","description":"A related resource from which the described resource is derived","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]},{"id":49,"url":"http:\/\/localhost\/Omeka\/api\/elements\/49","order":3,"name":"Subject","description":"The topic of the resource","comment":"","element_set":{"id":1,"url":"http:\/\/localhost\/Omeka\/api\/element_sets\/1","resource":"element_sets"},"extended_resources":[]}]', true);
        $elementsData = array('Dublin Core' => $fakeElementData);
        $view = new ViewModel;
        $form = new MappingForm($this->getServiceLocator());
        $view->setVariable('elementsData', $elementsData);
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
}