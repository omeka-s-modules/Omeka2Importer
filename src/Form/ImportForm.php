<?php

namespace Omeka2Importer\Form;

use Laminas\Form\Form;

class ImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'endpoint',
            'type' => 'url',
            'options' => [
                'label' => 'Omeka Classic API endpoint', // @translate
                'info' => 'The URI of the Omeka Classic API endpoint', // @translate
            ],
            'attributes' => [
                'id' => 'endpoint',
                'required' => 'true',
            ],
        ]);
    }
}
