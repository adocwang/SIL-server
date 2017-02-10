<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 15:29
 */

namespace AppBundle\Service;


use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class OperationLogger
{
    private $user;
    private $em;

    public function __construct(User $user, EntityManager $em)
    {
        $this->user = $user;
        $this->em = $em;
    }

    public function logAction($module, $action, $data)
    {

    }

    public function logDelAction($module, $action, $data)
    {

    }

    public function logSetAction($module, $action, $data)
    {

    }

    public function logAddAction($module, $action, $data)
    {

    }
}