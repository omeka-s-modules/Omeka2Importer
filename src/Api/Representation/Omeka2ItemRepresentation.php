<?php
namespace Omeka2Importer\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class Omeka2ItemRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'last_modified' => $this->lastModified(),
            'endpoint'      => $this->endpoint(),
            'remote_id'     => $this->remoteId(),
            'o:item'        => $this->item()->getReference(),
            'o:job'         => $this->job()->getReference(),
        );
    }

    public function lastModified()
    {
        return $this->getData()->getlastModified();
    }

    public function endpoint()
    {
        return $this->getData()->getEndpoint();
    }
    
    public function remoteId()
    {
        return $this->getData()->getRemoteId();
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation(null, $this->getData()->getItem());
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation(null, $this->getData()->getJob());
    }

}