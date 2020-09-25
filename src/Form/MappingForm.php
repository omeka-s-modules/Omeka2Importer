<?php

namespace Omeka2Importer\Form;

use Laminas\Form\Form;
use Omeka\Form\Element\ResourceSelect;

class MappingForm extends Form
{
    protected $owner;

    public function init()
    {
        $this->add([
            'name' => 'key',
            'type' => 'text',
            'options' => [
                'label' => 'Omeka 2 API key', // @translate
                'info' => 'Your API key for this site', // @translate
            ],
            'attributes' => [
                'id' => 'key',
            ],
        ]);

        $this->add([
            'name' => 'comment',
            'type' => 'textarea',
            'options' => [
                'label' => 'Comment', // @translate
                'info' => 'A note about the purpose or source of this import', // @translate
            ],
            'attributes' => [
                'id' => 'comment',
            ],
        ]);

        $this->add([
            'name' => 'itemSet',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'select-item-set',
                'required' => false,
                'multiple' => true,
                'data-placeholder' => 'Select item sets', // @translate
                'rows' => 6,
            ],
            'options' => [
                'label' => 'Import into', // @translate
                'info' => 'Optional. Import items into this item set. It is recommended to create an Item Set for each Omeka 2 site you import.', // @translate
                'resource_value_options' => [
                    'resource' => 'item_sets',
                    'query' => ['owner_id' => $this->getOwner()],
                    'option_text_callback' => function ($itemSet) {
                        return $itemSet->displayTitle();
                    },
                ],
            ],
        ]);

        $this->add([
            'name' => 'perPage',
            'type' => 'text',
            'options' => [
                'label' => 'Per page', // @translate
                'info' => 'Optional. Only retrieve this many records for each request.', // @translate
            ],
            'attributes' => [
                'id' => 'per-page',
            ],
        ]);

        $this->add([
            'name' => 'update',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Update a previous import', // @translate
                'info' => 'If checked, items will be reimported and all data replaced, including Item Set membership as set on this page.', // @translate
            ],
            'attributes' => [
                'id' => 'update',
            ],
        ]);

        $this->add([
            'name' => 'importCollections',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Import Collections', // @translate
                'info' => 'Import Omeka 2 collections as item sets. Items will be added to the new item sets.', // @translate
            ],
            'attributes' => [
                'id' => 'import-collections',
            ],
        ]);

        $this->add([
            'name' => 'tagPropertyId',
            'type' => 'Omeka\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Import tag as', // @translate
                'info' => 'Import tag into this property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'tag-property',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a property', // @translate
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'itemSet',
            'required' => false,
        ]);

        $inputFilter->add([
            'name' => 'key',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'tagPropertyId',
            'required' => false,
        ]);
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
