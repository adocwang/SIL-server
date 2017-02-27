<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * VCCompany
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\VCCompanyRepository")
 */
class VCCompany
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
     * @ORM\Column(type="string", length=63, nullable=false, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=63, nullable=true, unique=false)
     */
    private $vcName;

    /**
     * @var \DateTime $childrenSynced
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $childrenSynced;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $state = 1;

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'vc_name' => $this->getVcName(),
            'state' => $this->getState()
        ];
    }

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
     * Set name
     *
     * @param string $name
     *
     * @return VCCompany
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set vcName
     *
     * @param string $vcName
     *
     * @return VCCompany
     */
    public function setVcName($vcName)
    {
        $this->vcName = $vcName;

        return $this;
    }

    /**
     * Get vcName
     *
     * @return string
     */
    public function getVcName()
    {
        return $this->vcName;
    }

    /**
     * Set state
     *
     * @param integer $state
     *
     * @return VCCompany
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set childrenSynced
     *
     * @param \DateTime $childrenSynced
     *
     * @return VCCompany
     */
    public function setChildrenSynced($childrenSynced)
    {
        $this->childrenSynced = $childrenSynced;

        return $this;
    }

    /**
     * Get childrenSynced
     *
     * @return \DateTime
     */
    public function getChildrenSynced()
    {
        return $this->childrenSynced;
    }
}
