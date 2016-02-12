<?php
namespace Omeka2Importer\Form;

use Omeka\Form\AbstractForm;
use Omeka\Form\Element\ResourceSelect;
use Zend\Validator\Callback;
use Zend\Form\Element\Select;


class MappingForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'key',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Omeka 2 Api Key'),
                'info'  => $translator->translate('Your Api key for this site')
            ),
            'attributes' => array(
                'id' => 'key'
            )
        ));

        $this->add(array(
            'name' => 'comment',
            'type' => 'textarea',
            'options' => array(
                'label' => $translator->translate('Comment'),
                'info'  => $translator->translate('A note about the purpose or source of this import.')
            ),
            'attributes' => array(
                'id' => 'comment'
            )
        ));

        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');

        $itemSetSelect = new ResourceSelect($serviceLocator);
        $itemSetSelect->setName('itemSet')
            ->setLabel('Import into')
            ->setOption('info', $translator->translate('Optional. Import items into this item set. It is recommended to create an Item Set for each Omeka 2 site you import.'))
            ->setEmptyOption('Select Item Set...')
            ->setResourceValueOptions(
                'item_sets',
                array('owner_id' => $auth->getIdentity()),
                function ($itemSet, $serviceLocator) {
                    return $itemSet->displayTitle('[no title]');
                }
            );
        $this->add($itemSetSelect);
        
        $this->add(array(
            'name' => 'importCollections',
            'type' => 'checkbox',
            'options' => array(
                'label' => $translator->translate("Import Collections"),
                'info'  => $translator->translate("Import Omeka 2 collections as Item Sets. Items will be added to the new Item Sets.")
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
}