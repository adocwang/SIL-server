<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 15:29
 */

namespace AppBundle\Service;


use AppBundle\Entity\Log;
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
     */
    public function writeLog($module, $action, $data)
    {
        $user = $this->tokenStorage->getToken()->getUser();
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
     * @param $id integer
     */
    public function logDeleteAction($module, $id)
    {
        $data = ['id' => $id];
        $this->writeLog($module, 'delete', $data);
    }

    /**
     * @param $module string
     * @param $data array
     */
    public function logUpdateAction($module, $data)
    {
        $this->writeLog($module, 'update', json_encode($data));
    }

    /**
     * @param $module string
     * @param $data array
     */
    public function logCreateAction($module, $data)
    {
        $this->writeLog($module, 'update', json_encode($data));
    }
}