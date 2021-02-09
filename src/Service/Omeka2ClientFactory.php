<?php
namespace Omeka2Importer\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class Omeka2ClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Omeka2Client($services->get('Omeka\HttpClient'));
    }
}
