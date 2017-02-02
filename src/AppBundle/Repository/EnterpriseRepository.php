<?php

namespace AppBundle\Repository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * EnterpriseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EnterpriseRepository extends \Doctrine\ORM\EntityRepository
{
    public function listPage($page, $pageLimit = 20, $condition)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('a')
            ->from('AppBundle:Enterprise', 'a');
        if (!empty($condition['name'])) {
            $queryBuilder->andWhere('a.name = :name');
            $queryBuilder->setParameter('name', $condition['name']);
        }
        if (!empty($condition['state'])) {
            $queryBuilder->andWhere('a.state = :state');
            $queryBuilder->setParameter('state', $condition['state']);
        }
        if (!empty($condition['bank'])) {
            $queryBuilder->andWhere('e.bank = :bank');
            $queryBuilder->setParameter('bank', $condition['bank']);
        }
        $query = $queryBuilder->orderBy('e.id', 'DESC')->getQuery();
        $query->setFirstResult(($page - 1) * $pageLimit)->setMaxResults($pageLimit);
        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $result = [
            'pageCount' => ceil($paginator->count() / $pageLimit),
            'data' => $paginator->getIterator()
        ];
        return $result;

    }
}
