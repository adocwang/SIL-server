<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/29/17
 * Time: 21:07
 */

namespace AppBundle\Voter;


use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (!$subject instanceof User) {
            return false;
        }

        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $nowUser = $token->getUser();
        if (!$nowUser instanceof User || !$subject instanceof User) {
            return false;
        }
        if ($attribute == 'view') {
            return true;
        } elseif ($attribute == 'edit' || $attribute == 'add') {
            //自己能够编辑自己
            if ($subject->getId() == $nowUser->getId()) {
                return true;
            }
            //客户经理只有行长和管理员能操作
            $subjectRole = $subject->getRole()->getRole();
            $nowUserRole = $nowUser->getRole()->getRole();
            if ($subjectRole == 'ROLE_CUSTOMER_MANAGER' &&
                in_array($nowUserRole, ['ROLE_END_PRESIDENT', 'ROLE_BRANCH_PRESIDENT', 'ROLE_ADMIN'])
            ) {
                return true;
            }
            //支行行长只有admin和分行才能操作
            if ($subjectRole == 'ROLE_END_PRESIDENT' &&
                in_array($nowUserRole, ['ROLE_BRANCH_PRESIDENT', 'ROLE_ADMIN'])
            ) {
                return true;
            }
            //分行行长只有admin和分行才能操作
            if ($subjectRole == 'ROLE_BRANCH_PRESIDENT' &&
                in_array($nowUserRole, ['ROLE_ADMIN'])
            ) {
                return true;
            }
            //admin只有admin才能操作
            if ($subjectRole == 'ROLE_ADMIN' &&
                in_array($nowUserRole, ['ROLE_ADMIN'])
            ) {
                return true;
            }
        }
        return false;
    }
}