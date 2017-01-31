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
}
