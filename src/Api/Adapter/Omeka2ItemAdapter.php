<?php
namespace Omeka2Importer\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class Omeka2ItemAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka2Importer\Entity\Omeka2Item';
    }
    
    public function getResourceName()
    {
        return 'omeka2_items';
    }
    
    public function getRepresentationClass()
    {
        return 'Omeka2Importer\Api\Representation\Omeka2ItemRepresentation';
    }
    
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['endpoint'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.endpoint',
                $this->createNamedParameter($qb, $query['endpoint']))
            );
        }

        if (isset($query['job_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.job',
                $this->createNamedParameter($qb, $query['job_id']))
            );
        }
        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.item',
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
        if (isset($query['remote_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.remoteId',
                $this->createNamedParameter($qb, $query['remote_id']))
            );
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (isset($data['o:job']['o:id'])) {
            $job = $this->getAdapter('jobs')->findEntity($data['o:job']['o:id']);
            $entity->setJob($job);
        }
        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        if (isset($data['endpoint'])) {
            $entity->setEndpoint($data['endpoint']);
        }

        if (isset($data['remote_id'])) {
            $entity->setRemoteId($data['remote_id']);
        }

        if (isset($data['last_modified'])) {
            $entity->setLastModified($data['last_modified']);
        }
    }
}
