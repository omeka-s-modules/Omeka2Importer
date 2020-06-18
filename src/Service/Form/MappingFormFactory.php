<?php

namespace Omeka2Importer\Service\Form;

use Omeka2Importer\Form\MappingForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MappingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $form = new MappingForm();
        $identity = $container->get('Omeka\AuthenticationService')->getIdentity();
        $form->setOwner($identity);

        return $form;
    }
}
