<?php
namespace Omeka2Importer\Service\Form;

use Zend\ServiceManager\setCreationOptions;

use Omeka2Importer\Form\MappingForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MappingFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $form = new MappingForm(null, $this->options);
        $serviceLocator = $elements->getServiceLocator();
        $identity = $serviceLocator->get('Omeka\AuthenticationService')->getIdentity();
        $form->setOwner($identity);
        return $form;
    }
}
