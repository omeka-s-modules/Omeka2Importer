<?php

namespace Omeka2Importer\Form;

use Zend\Form\Form;
use Omeka\Form\Element\ResourceSelect;
use Zend\Form\Element\Select;

class MappingForm extends Form
{
    protected $owner;

    public function init()
    {
        $this->add(array(
            'name' => 'key',
            'type' => 'text',
            'options' => array(
                'label' => 'Omeka 2 Api Key', // @translate
                'info' => 'Your Api key for this site', // @translate
            ),
            'attributes' => array(
                'id' => 'key',
            ),
        ));

        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Comment', // @translate
                'info' => 'A note about the purpose or source of this import.', // @translate
            ),
            'attributes' => array(
                'id' => 'comment',
            ),
        ));

        $this->add([
            'name' => 'itemSet',
            'type' => ResourceSelect::class,
            'options' => [
                'label' => 'Import into', // @translate
                'info' => 'Optional. Import items into this item set. It is recommended to create an Item Set for each Omeka 2 site you import.', // @translate
                'empty_option' => 'Select Item Set...', // @translate
                'resource_value_options' => [
                    'resource' => 'item_sets',
                    'query' => ['owner_id' => $this->getOwner()],
                    'option_text_callback' => function ($itemSet) {
                        return $itemSet->displayTitle();
                    },
                ],
            ],
        ]);

        $this->add(array(
            'name' => 'importCollections',
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Import Collections', // @translate
                'info' => 'Import Omeka 2 collections as Item Sets. Items will be added to the new Item Sets.', // @translate
            ),
        ));

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'itemSet',
            'required' => false,
        ));

        $inputFilter->add(array(
            'name' => 'key',
            'required' => false,
        ));
    }

    public function setOwner($identity)
    {
        $this->owner = $identity;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
