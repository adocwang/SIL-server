<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Loan
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LoanRepository")
 */
class Loan
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
     * Many Loan in one Enterprise.
     * @ORM\ManyToOne(targetEntity="Enterprise")
     * @ORM\JoinColumn(name="enterprise_id", referencedColumnName="id")
     */
    private $enterprise;

    /**
     * Many Loan in one Bank.
     * @ORM\ManyToOne(targetEntity="Bank")
     * @ORM\JoinColumn(name="bank_id", referencedColumnName="id")
     */
    private $bank;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $moreData;


    /**
     * 0:已受理，1：协理已通过，2：支行已通过，3：分行已通过，4：审批通过，5：签约，6：放款
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $progress = 0;


    /**
     * 1：正常，2：已冻结，3：已删除
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    private $state = 1;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

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
            'moreData' => json_decode($this->getMoreData(), true),
            'progress' => $this->getProgress(),
            'state' => $this->getState(),
            'created' => $this->getCreated()->format('Y-m-d H:i:s'),
            'modified' => $this->getModified()->format('Y-m-d H:i:s')
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
     * Set moreData
     *
     * @param string $moreData
     *
     * @return Loan
     */
    public function setMoreData($moreData)
    {
        $this->moreData = $moreData;

        return $this;
    }

    /**
     * Get moreData
     *
     * @return string
     */
    public function getMoreData()
    {
        return $this->moreData;
    }

    /**
     * Set state
     *
     * @param integer $state
     *
     * @return Loan
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
     * @return Loan
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
     * @return Loan
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
     * Set progress
     *
     * @param integer $progress
     *
     * @return Loan
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
     * Set bank
     *
     * @param \AppBundle\Entity\Bank $bank
     *
     * @return Loan
     */
    public function setBank(\AppBundle\Entity\Bank $bank = null)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get bank
     *
     * @return \AppBundle\Entity\Bank
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * Set enterprise
     *
     * @param \AppBundle\Entity\Enterprise $enterprise
     *
     * @return Loan
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
}
