<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 15:29
 */

namespace AppBundle\Service;


use AppBundle\Entity\Log;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class OperationLogger
{
    private $tokenStorage;
    private $em;

    public function __construct(TokenStorage $tokenStorage, EntityManager $em)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    /**
     * @param $module string
     * @param $action string
     * @param $data string
     * @param User $user
     */
    public function writeLog($module, $action, $data, User $user = null)
    {
        if (empty($user)) {
            $user = $this->tokenStorage->getToken()->getUser();
        }
        $log = new Log();
        $log->setModule($module);
        $log->setAction($action);
        $log->setData($data);
        $log->setCreatedBy($user);
        $this->em->persist($log);
        $this->em->flush();
    }

    /**
     * @param $module string
     * @param $data array
     */
    public function logDeleteAction($module, $data)
    {
        $this->writeLog($module, '删除', json_encode($data));
    }

    /**
     * @param $module string
     * @param $data array
     */
    public function logUpdateAction($module, $data)
    {
        $this->writeLog($module, '修改', json_encode($data));
    }

    /**
     * @param $module string
     * @param $data array
     * @internal param array $data
     */
    public function logCreateAction($module, $data)
    {
        $this->writeLog($module, '添加', json_encode($data));
    }

    /**
     * @param $module string
     * @param $action
     * @param $data
     * @param User $user
     * @internal param $id
     * @internal param array $data
     */
    public function logOtherAction($module, $action, $data, User $user = null)
    {
        $this->writeLog($module, $action, json_encode($data), $user);
    }
}