<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 02:13
 */

namespace AppBundle\Entity;

use AppBundle\Validator\Constraints as MyAssert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EnterpriseRepository")
 * @ORM\Table(name="enterprise")
 */
class Enterprise
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=127, unique=true, nullable=false)
     */
    private $name;

    /**
     * @var \DateTime $detailSynced
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $detailSynced;

    /**
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $legalMan;

    /**
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $registCapi;

    /**
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $qixinId;

    /**
     * @ORM\Column(type="string", length=63, nullable=true)
     */
    private $detailObjId;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $start;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

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

    /**
     * 被过滤的原因
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $blockReason = '';

    /**
     * 1正常2冻结3删除
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $state = 1;

    /**
     * 1：待分配，2：已分配（待认领），3：已认领
     *
     * @ORM\Column(type="integer", nullable=false,options={"default":1})
     */
    protected $distributeState = 1;


    /**
     * Many Enterprises in one Bank.
     * @ORM\ManyToOne(targetEntity="Bank")
     * @ORM\JoinColumn(name="bank_id", referencedColumnName="id")
     */
    private $bank;

    /**
     * Many RoleA in one User.
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="role_a_id", referencedColumnName="id")
     */
    private $roleA;

    /**
     * Many RoleB in one User.
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="role_b_id", referencedColumnName="id")
     */
    private $roleB;


    /**
     * One Customer has One Cart.
     * @ORM\OneToOne(targetEntity="Finding", mappedBy="enterprise")
     */
    private $finding;

    function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'legal_man' => $this->getLegalMan(),
            'regist_capi' => $this->getRegistCapi(),
            'start' => $this->getStart() ? $this->getStart()->format('Y-m-d') : "",
            'address' => $this->getAddress(),
            'bank' => $this->getBank() ? $this->getBank()->toArrayNoSubordinate() : null,
            'role_a' => $this->getRoleA() ? $this->getRoleA()->getOtherArr() : null,
            'role_b' => $this->getRoleB() ? $this->getRoleB()->getOtherArr() : null,
            'state' => $this->getState(),
            'distribute_state' => $this->getDistributeState(),
            'block_reason' => $this->getBlockReason(),
            'qixin_id' => $this->getQixinId()
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
     * @return Enterprise
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
     * Set legalMan
     *
     * @param string $legalMan
     *
     * @return Enterprise
     */
    public function setLegalMan($legalMan)
    {
        $this->legalMan = $legalMan;

        return $this;
    }

    /**
     * Get legalMan
     *
     * @return string
     */
    public function getLegalMan()
    {
        return $this->legalMan;
    }

    /**
     * Set start
     *
     * @param \DateTime $start
     *
     * @return Enterprise
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Enterprise
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return Enterprise
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
     * @return Enterprise
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
     * Set state
     *
     * @param integer $state
     *
     * @return Enterprise
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
     * Set bank
     *
     * @param \AppBundle\Entity\Bank $bank
     *
     * @return Enterprise
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
     * Set roleA
     *
     * @param \AppBundle\Entity\User $roleA
     *
     * @return Enterprise
     */
    public function setRoleA(\AppBundle\Entity\User $roleA = null)
    {
//        if ($this->getRoleB() == $roleA) {
//            return $this;
//        }
        $this->roleA = $roleA;
        return $this;
    }

    /**
     * Get roleA
     *
     * @return \AppBundle\Entity\User
     */
    public function getRoleA()
    {
        return $this->roleA;
    }

    /**
     * Set roleB
     *
     * @param \AppBundle\Entity\User $roleB
     *
     * @return Enterprise
     */
    public function setRoleB(\AppBundle\Entity\User $roleB = null)
    {
//        if ($this->getRoleA() == $roleB) {
//            return $this;
//        }
        $this->roleB = $roleB;
        return $this;
    }

    /**
     * Get roleB
     *
     * @return \AppBundle\Entity\User
     */
    public function getUserB()
    {
        return $this->roleB;
    }

    /**
     * Get roleB
     *
     * @return \AppBundle\Entity\User
     */
    public function getRoleB()
    {
        return $this->roleB;
    }

    /**
     * Set detailSynced
     *
     * @param \DateTime $detailSynced
     *
     * @return Enterprise
     */
    public function setDetailSynced($detailSynced)
    {
        $this->detailSynced = $detailSynced;

        return $this;
    }

    /**
     * Get detailSynced
     *
     * @return \DateTime
     */
    public function getDetailSynced()
    {
        return $this->detailSynced;
    }

    /**
     * Set finding
     *
     * @param \AppBundle\Entity\Finding $finding
     *
     * @return Enterprise
     */
    public function setFinding(\AppBundle\Entity\Finding $finding = null)
    {
        $this->finding = $finding;

        return $this;
    }

    /**
     * Get finding
     *
     * @return \AppBundle\Entity\Finding
     */
    public function getFinding()
    {
        return $this->finding;
    }

    /**
     * Set registCapi
     *
     * @param string $registCapi
     *
     * @return Enterprise
     */
    public function setRegistCapi($registCapi)
    {
        $this->registCapi = $registCapi;

        return $this;
    }

    /**
     * Get registCapi
     *
     * @return string
     */
    public function getRegistCapi()
    {
        return $this->registCapi;
    }

    /**
     * Set detailObjId
     *
     * @param string $detailObjId
     *
     * @return Enterprise
     */
    public function setDetailObjId($detailObjId)
    {
        $this->detailObjId = $detailObjId;

        return $this;
    }

    /**
     * Get detailObjId
     *
     * @return string
     */
    public function getDetailObjId()
    {
        return $this->detailObjId;
    }

    /**
     * Set blockReason
     *
     * @param string $blockReason
     *
     * @return Enterprise
     */
    public function setBlockReason($blockReason)
    {
        $this->blockReason = $blockReason;

        return $this;
    }

    /**
     * Get blockReason
     *
     * @return string
     */
    public function getBlockReason()
    {
        return $this->blockReason;
    }

    /**
     * Set qixinId
     *
     * @param string $qixinId
     *
     * @return Enterprise
     */
    public function setQixinId($qixinId)
    {
        $this->qixinId = $qixinId;

        return $this;
    }

    /**
     * Get qixinId
     *
     * @return string
     */
    public function getQixinId()
    {
        return $this->qixinId;
    }

    /**
     * Set distributeState
     *
     * @param integer $distributeState
     *
     * @return Enterprise
     */
    public function setDistributeState($distributeState)
    {
        $this->distributeState = $distributeState;

        return $this;
    }

    /**
     * Get distributeState
     *
     * @return integer
     */
    public function getDistributeState()
    {
        return $this->distributeState;
    }
}
