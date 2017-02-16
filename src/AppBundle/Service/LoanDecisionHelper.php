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

class LoanDecisionHelper
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDataForm($enterpriseId)
    {
        $enterprise = $this->em->getRepository('AppBundle:Enterprise')->find($enterpriseId);
        if(empty($enterprise)){

        }
        $comapnyDetail =
    }

    private function buildEmptyDataForm(){
        $configure=$this->em->getRepository('AppBundle:ClientConfig')->findOneByKey('');
    }
}