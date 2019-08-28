<?php
namespace Omeka2Importer\Form;

use Zend\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'checkbox',
            'name' => 'import_classic',
            'options' => [
                'label' => 'Enable improved data mapping', // @translate
                'info' => 'Check this box if you want to import the "Omeka Classic" vocabulary and resource templates. They may improve data mapping between Omeka Classic (Omeka 2) and Omeka S.', // @translate
            ],
        ]);
    }
}
