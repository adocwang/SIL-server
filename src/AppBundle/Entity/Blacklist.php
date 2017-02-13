<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Role
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BlacklistRepository")
 */
class Blacklist
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
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $matchCount = 0;
    

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
     * @return Blacklist
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
     * Set matchCount
     *
     * @param integer $matchCount
     *
     * @return Blacklist
     */
    public function setMatchCount($matchCount)
    {
        $this->matchCount = $matchCount;

        return $this;
    }

    /**
     * Get matchCount
     *
     * @return integer
     */
    public function getMatchCount()
    {
        return $this->matchCount;
    }
}
