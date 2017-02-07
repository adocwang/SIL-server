<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/8/17
 * Time: 00:09
 */

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
/**
 * @MongoDB\Document
 */
class EnterpriseDetail
{
    /**
     * @MongoDB\Id
     */
    protected $id;
}