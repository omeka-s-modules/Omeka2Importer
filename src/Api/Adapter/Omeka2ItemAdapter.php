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
        if (isset($query['uri'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.uri',
                $this->createNamedParameter($qb, $query['uri']))
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
        if (isset($data['uri'])) {
            $entity->setUri($data['uri']);
        }

        if (isset($data['last_modified'])) {
            $entity->setLastModified($data['last_modified']);
        }
    }
}
