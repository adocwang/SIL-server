<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/15/17
 * Time: 16:45
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/** @MongoDB\Document(collection="enterprise_detail") */
class EnterpriseDetail
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Hash
     */
    protected $detail = [];

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
     * Set detail
     *
     * @param array $detail
     * @return self
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * Get detail
     *
     * @return hash $detail
     */
    public function getDetail()
    {
        return $this->detail;
    }
}
