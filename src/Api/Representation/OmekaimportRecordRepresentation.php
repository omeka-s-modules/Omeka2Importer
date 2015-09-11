<?php
namespace Omeka2Importer\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OmekaimportRecordRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'last_modified' => $this->lastModified(),
            'endpoint'      => $this->endpoint(),
            'remote_type'   => $this->remoteType(),
            'remote_id'     => $this->remoteId(),
            'o:item'        => $this->item()->getReference(),
            'o:item_set'    => $this->itemSet()->getReference(),
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
    
    public function remoteType()
    {
        $this->getData()->getRemoteType();
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

    public function itemSet()
    {
        return $this->getAdapter('item_sets')
            ->getRepresentation(null, $this->getData()->getItemSet());
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation(null, $this->getData()->getJob());
    }

}