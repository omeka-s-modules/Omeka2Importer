<?php
namespace Omeka2Importer\Entity;

use DateTime;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Job;
use Omeka\Entity\Item;

/**
 * @Entity
 */
class Omeka2Item extends AbstractEntity
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Job")
     * @JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     * @var int
     */
    protected $item;

    /**
     * @Column(type="integer")
     * @var int
     */
    public $remoteItemId;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $uri;

    /**
     * @Column(type="datetime")
     */
    protected $lastModified;

    public function getId()
    {
        return $this->id;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setLastModified(DateTime $lastModified) 
    {
        $this->lastModified = $lastModified;
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }
}