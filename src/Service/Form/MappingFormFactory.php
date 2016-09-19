<?php

namespace Omeka2Importer\Service\Form;

use Omeka2Importer\Form\MappingForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MappingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new MappingForm(null, $options);
        $identity = $services->get('Omeka\AuthenticationService')->getIdentity();
        $form->setOwner($identity);

        return $form;
    }
}
