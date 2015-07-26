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
    public $remoteId;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $endpoint;

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

    public function setEndpoint($uri)
    {
        $this->endpoint = $uri;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }
    
    public function setRemoteId($id)
    {
        $this->remoteId = $id;
    }
    
    public function getRemoteId()
    {
        return $this->remoteId;
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