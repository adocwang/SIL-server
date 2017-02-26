<?php

namespace AppBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
    public function listPage($page, $pageLimit = 20, $condition)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('AppBundle:User', 'u');
        if (!empty($condition['phone'])) {
            $queryBuilder->andWhere('u.phone = :phone');
            $queryBuilder->setParameter('phone', $condition['phone']);
        }
        if (!empty($condition['true_name'])) {
            $queryBuilder->andWhere('u.trueName = :true_name');
            $queryBuilder->setParameter('true_name', $condition['true_name']);
        }
        if (!empty($condition['bank'])) {
            $queryBuilder->andWhere('u.bank = :bank');
            $queryBuilder->setParameter('bank', $condition['bank']);
        }
        if (!empty($condition['role'])) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('u.role', ':role'));
            $queryBuilder->setParameter('role', $condition['role']);
        }
        if (!empty($condition['state'])) {
            $queryBuilder->andWhere('u.state = :state');
            $queryBuilder->setParameter('state', $condition['state']);
        }
        $query = $queryBuilder->orderBy('u.id', 'DESC')
            ->getQuery();
        $query->setFirstResult(($page - 1) * $pageLimit)
            ->setMaxResults($pageLimit);
        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $result = [
            'count' => $paginator->count(),
            'pageCount' => ceil($paginator->count() / $pageLimit),
            'data' => $paginator->getIterator()
        ];
        return $result;

    }
}
