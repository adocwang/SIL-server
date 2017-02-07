<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ClientConfig
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientConfigRepository")
 */
class ClientConfig
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
    private $configKey;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=65535)
     */
    private $configValue;

    /**
     * 0:all，1：web，2：app，3：ios only，4：android only
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $type = 0;

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
     * Set configKey
     *
     * @param string $configKey
     *
     * @return ClientConfig
     */
    public function setConfigKey($configKey)
    {
        $this->configKey = $configKey;

        return $this;
    }

    /**
     * Get configKey
     *
     * @return string
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }

    /**
     * Set configValue
     *
     * @param string $configValue
     *
     * @return ClientConfig
     */
    public function setConfigValue($configValue)
    {
        $this->configValue = $configValue;

        return $this;
    }

    /**
     * Get configValue
     *
     * @return string
     */
    public function getConfigValue()
    {
        return $this->configValue;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return ClientConfig
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }
}
