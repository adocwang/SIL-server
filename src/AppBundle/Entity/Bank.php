<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Role
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BankRepository")
 */
class Bank
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
     * @ORM\Column(type="string", length=63, unique=true)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Bank", inversedBy="subordinate")
     * @ORM\JoinColumn(name="superior_id", referencedColumnName="id")
     */
    private $superior;

    /**
     * @ORM\OneToMany(targetEntity="Bank", mappedBy="superior")
     */
    private $subordinate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $state = 1;

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
     * @return Bank
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
     * Set superior
     *
     * @param \AppBundle\Entity\Bank $superior
     *
     * @return Bank
     */
    public function setSuperior(\AppBundle\Entity\Bank $superior = null)
    {
        $this->superior = $superior;

        return $this;
    }

    /**
     * Get superior
     *
     * @return \AppBundle\Entity\Bank
     */
    public function getSuperior()
    {
        return $this->superior;
    }

    public function toArray()
    {
        $subordinateObjs = $this->getSubordinate();
        $subordinate = [];
        /**
         * @var Bank $subordinateObj
         */
        if (!empty($subordinateObjs)) {
            foreach ($subordinateObjs as $subordinateObj) {
                $subordinate[] = $subordinateObj->toArray();
            }
        }
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'state' => $this->getState(),
            'subordinate' => $subordinate,
        ];
    }

    public function toArrayNoSubordinate()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'state' => $this->getState(),
            'superior_id' => $this->getSuperior()->getId()
        ];
    }

    /**
     * Set state
     *
     * @param integer $state
     *
     * @return Bank
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
     * Constructor
     */
    public function __construct()
    {
        $this->subordinate = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add subordinate
     *
     * @param \AppBundle\Entity\Bank $subordinate
     *
     * @return Bank
     */
    public function addSubordinate(\AppBundle\Entity\Bank $subordinate)
    {
        $this->subordinate[] = $subordinate;

        return $this;
    }

    /**
     * Remove subordinate
     *
     * @param \AppBundle\Entity\Bank $subordinate
     */
    public function removeSubordinate(\AppBundle\Entity\Bank $subordinate)
    {
        $this->subordinate->removeElement($subordinate);
    }

    /**
     * Get subordinate
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubordinate()
    {
        return $this->subordinate;
    }
}
