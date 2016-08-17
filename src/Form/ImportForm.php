<?php

namespace Omeka2Importer\Form;

use Zend\Form\Form;

class ImportForm extends Form
{
    public function init()
    {
        $this->add(array(
            'name' => 'endpoint',
            'type' => 'url',
            'options' => array(
                'label' => 'Omeka 2 Api Endpoint', // @translate
                'info' => 'The URI of the Omeka 2 Api Endpoint', // @translate
            ),
            'attributes' => array(
                'id' => 'endpoint',
                'required' => 'true',
            ),
        ));
    }
}
