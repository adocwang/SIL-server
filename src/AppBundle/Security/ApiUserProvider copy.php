<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/26/17
 * Time: 11:59
 */

namespace AppBundle\Security;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiUserProvider implements UserProviderInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadUserByToken($token)
    {
        return $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->where('u.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function loadUserByUsername($username)
    {
        return $this->em->getRepository('AppBundle:User')->createQueryBuilder('u')
            ->where('u.phone = :phone')
            ->setParameter('phone', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return 'Symfony\Component\Security\Core\User\User' === $class;
    }
}