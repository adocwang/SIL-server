<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Log
 *
 * @ORM\Table(name="log")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LogRepository")
 */
class Log
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $object;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63)
     */
    private $action;

    /**
     * @var User $createdBy
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set object
     *
     * @param string $object
     *
     * @return Log
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object
     *
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set action
     *
     * @param string $action
     *
     * @return Log
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Log
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set createdBy
     *
     * @param \AppBundle\Entity\User $createdBy
     *
     * @return Log
     */
    public function setCreatedBy(\AppBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \AppBundle\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}
