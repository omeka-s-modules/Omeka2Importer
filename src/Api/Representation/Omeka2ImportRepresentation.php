<?php
namespace Omeka2Importer\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class Omeka2ImportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $undo_job = null;
        if($this->undoJob()) {
            $undo_job = $this->undoJob()->getReference();
        }

        return array(
            'added_count'    => $this->addedCount(),
            'updated_count'  => $this->updatedCount(),
            'comment'        => $this->comment(),
            'o:job'          => $this->job()->getReference(),
            'o:undo_job'     => $undo_job
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
