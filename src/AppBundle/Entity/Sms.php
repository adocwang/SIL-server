<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Role
 *
 * @ORM\Table(name="sms")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SmsRepository")
 */
class Sms
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=31)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=31)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=31)
     */
    private $code;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $used;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return Sms
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Sms
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Sms
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Sms
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
     * Set used
     *
     * @param \DateTime $used
     *
     * @return Sms
     */
    public function setUsed($used)
    {
        $this->used = $used;

        return $this;
    }

    /**
     * Get used
     *
     * @return \DateTime
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Sms
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
