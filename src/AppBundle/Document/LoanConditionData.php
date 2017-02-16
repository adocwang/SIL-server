<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/15/17
 * Time: 16:45
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/** @MongoDB\Document(collection="loan_condition_data") */
class LoanConditionData
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="integer")
     */
    protected $enterpriseId;

    /**
     * @MongoDB\Hash
     */
    protected $data = [];

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set enterpriseId
     *
     * @param integer $enterpriseId
     * @return self
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterpriseId = $enterpriseId;
        return $this;
    }

    /**
     * Get enterpriseId
     *
     * @return integer $enterpriseId
     */
    public function getEnterpriseId()
    {
        return $this->enterpriseId;
    }

    /**
     * Set data
     *
     * @param hash $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get data
     *
     * @return hash $data
     */
    public function getData()
    {
        return $this->data;
    }
}
