<?php
namespace Omeka2Importer\Form;

use Omeka\Form\AbstractForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;

class ImportForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'endpoint',
            'type' => 'url',
            'options' => array(
                'label' => $translator->translate('Omeka 2 Api Endpoint'),
                'info'  => $translator->translate('The URI of the Omeka 2 Api Endpoint')
            ),
            'attributes' => array(
                'id' => 'endpoint',
                'required' => 'true'
            )
        ));

        $this->remove('csrf');
    }
}