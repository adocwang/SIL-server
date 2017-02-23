<?php

namespace AppBundle\Repository;

use AppBundle\Constant\State;
use AppBundle\Entity\User;
use Doctrine\DBAL\Query\QueryBuilder;
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
            $queryBuilder->andWhere($queryBuilder->expr()->like('a.name', ':name'));
            $queryBuilder->setParameter('name', '%' . $condition['name'] . '%');
        }
        if (!empty($condition['state'])) {
            $queryBuilder->andWhere('a.state = :state');
            $queryBuilder->setParameter('state', $condition['state']);
        }
        if (!empty($condition['bank'])) {
            $queryBuilder->andWhere('a.bank = :bank');
            $queryBuilder->setParameter('bank', $condition['bank']);
        }
        if (!empty($condition['my_finding']) && $condition['my_finding'] == 1) {
            $queryBuilder->leftJoin('a.finding', 'f', 'WITH');
            $queryBuilder->andWhere('a.roleA = :now_user OR (a.roleB = :now_user AND f.progress = 0)');
            $queryBuilder->setParameter('now_user', $condition['now_user']);
        } elseif (!empty($condition['my_bank_finding']) && $condition['my_bank_finding'] == 1) {
            $queryBuilder->leftJoin('a.finding', 'f', 'WITH');
            $queryBuilder->andWhere('f.progress = 1');
        } elseif (!empty($condition['only_user']) && $condition['now_user'] instanceof User) {
            $queryBuilder->andWhere('a.roleA = :now_user OR a.roleB = :now_user');
            $queryBuilder->setParameter('now_user', $condition['now_user']);
        } elseif (!empty($condition['only_loan_ready']) && $condition['only_loan_ready'] == 1) {
            $queryBuilder->leftJoin('a.finding', 'f', 'WITH');
            $queryBuilder->andWhere('f.id IS NOT NULL');
        }
        if (!empty($condition['role_a_disable'])) {
            if ($condition['role_a_disable'] == 1) {
                $queryBuilder->leftJoin('a.roleA', 'r', 'WITH', 'r.state IN (' . State::STATE_UN_ACTIVE . ',' . State::STATE_FREEZED . ',' . State::STATE_DELETED . ')');
                $queryBuilder->andWhere('r.state IN (' . State::STATE_UN_ACTIVE . ',' . State::STATE_FREEZED . ',' . State::STATE_DELETED . ')');
            }
        }
        $query = $queryBuilder->orderBy('a.id', 'DESC')->getQuery();
        $query->setFirstResult(($page - 1) * $pageLimit)->setMaxResults($pageLimit);
        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $result = [
            'count' => $paginator->count(),
            'pageCount' => ceil($paginator->count() / $pageLimit),
            'data' => $paginator->getIterator()
        ];
        return $result;

    }
}
