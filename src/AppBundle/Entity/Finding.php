<?php

namespace AppBundle\Entity;

use AppBundle\Constant\State;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Finding
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FindingRepository")
 */
class Finding
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
     * One Finding of one Enterprise.
     * @ORM\OneToOne(targetEntity="Enterprise", inversedBy="finding")
     * @ORM\JoinColumn(name="enterprise_id", referencedColumnName="id")
     */
    private $enterprise;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $data;

    /**
     * 未通过原因
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $unPassReason = '';


    /**
     * 1：正常，2：已冻结，3：已删除
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $state = State::STATE_NORMAL;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;


    /**
     * -1:待采集（其实是重新采集），0:已提交，1：协理已通过，2：行长已通过, 3:审核不通过
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $progress = 0;

    /**
     * @var \DateTime $modified
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $modified;

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'data' => $this->getData(),
            'state' => $this->getState(),
            'progress' => $this->getProgress(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
            'modified' => $this->getModified()->format('Y-m-d H:i:s'),
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
     * Set data
     *
     * @param string $data
     *
     * @return Finding
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set state
     *
     * @param integer $state
     *
     * @return Finding
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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Finding
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
     * Set modified
     *
     * @param \DateTime $modified
     *
     * @return Finding
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set enterprise
     *
     * @param \AppBundle\Entity\Enterprise $enterprise
     *
     * @return Finding
     */
    public function setEnterprise(\AppBundle\Entity\Enterprise $enterprise = null)
    {
        $this->enterprise = $enterprise;

        return $this;
    }

    /**
     * Get enterprise
     *
     * @return \AppBundle\Entity\Enterprise
     */
    public function getEnterprise()
    {
        return $this->enterprise;
    }

    /**
     * Set progress
     *
     * @param integer $progress
     *
     * @return Finding
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return integer
     */
    public function getProgress()
    {
        return $this->progress;
    }


    /**
     * Set unPassReason
     *
     * @param string $unPassReason
     *
     * @return Finding
     */
    public function setUnPassReason($unPassReason)
    {
        $this->unPassReason = $unPassReason;

        return $this;
    }

    /**
     * Get unPassReason
     *
     * @return string
     */
    public function getUnPassReason()
    {
        return $this->unPassReason;
    }
}
