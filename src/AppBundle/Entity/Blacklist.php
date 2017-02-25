<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private $note;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $matchCount = 0;

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'source' => $this->getSource(),
            'note' => $this->getNote(),
            'match_count' => $this->getMatchCount(),
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

    /**
     * Set note
     *
     * @param string $note
     *
     * @return Blacklist
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return Blacklist
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
}
