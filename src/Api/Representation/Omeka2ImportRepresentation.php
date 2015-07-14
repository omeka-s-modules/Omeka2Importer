<?php
namespace Omeka2Importer\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class Omeka2ImportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'added_count'    => $this->getData()->getAddedCount(),
            'updated_count'  => $this->getData()->getUpdatedCount(),
            'comment'        => $this->getData()->getComment(),
            'o:job'          => $this->getReference(
                null,
                $this->getData()->getJob(),
                $this->getAdapter('jobs')
            ),
            'o:undo_job'     => $this->getReference(
                null,
                $this->getData()->getUndoJob(),
                $this->getAdapter('jobs')
            ),
        );
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation(null, $this->getData()->getJob());
    }

    public function undoJob()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation(null, $this->getData()->getUndoJob());
    }

    public function comment()
    {
        return $this->getData()->getComment();
    }

    public function addedCount()
    {
        return $this->getData()->getAddedCount();
    }

    public function updatedCount()
    {
        return $this->getData()->getUpdatedCount();
    }
}
