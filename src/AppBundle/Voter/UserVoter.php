<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/29/17
 * Time: 21:07
 */

namespace AppBundle\Voter;


use AppBundle\Entity\Role;
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
            //管理员干什么都可以
            if ($nowUser->getRole()->isRole(Role::ROLE_ADMIN)) {
                return true;
            }
            //自己能够编辑自己
            if ($subject->getId() == $nowUser->getId()) {
                return true;
            }

            //银行只能是自己银行和下级银行
            $right = false;
            $nowSuperior = $subject->getBank();
            do {
                if (!empty($nowSuperior)) {
                    if ($nowSuperior == $nowUser->getBank()) {
                        $right = true;
                        break;
                    }
                } else {
                    break;
                }
            } while ($nowSuperior = $nowSuperior->getSuperior());
            if (!$right) {
                return false;
            }
            //客户经理只有行长和管理员能操作
            if ($subject->getRole()->isRole(Role::ROLE_CUSTOMER_MANAGER) &&
                ($nowUser->getRole()->isRole(Role::ROLE_PRESIDENT) || $nowUser->getRole()->isRole(Role::ROLE_ADMIN))
            ) {
                return true;
            }
            //支行行长只有admin和分行才能操作
            if (($subject->getRole()->isRole(Role::ROLE_END_PRESIDENT) || $subject->getRole()->isRole(Role::ROLE_END_PRESIDENT_WITH_CM)) &&
                ($nowUser->getRole()->isRole(Role::ROLE_BRANCH_PRESIDENT) || $nowUser->getRole()->isRole(Role::ROLE_ADMIN))
            ) {
                return true;
            }
            //分行行长只有admin和分行才能操作
            if ($subject->getRole()->isRole(Role::ROLE_BRANCH_PRESIDENT) && $nowUser->getRole()->isRole(Role::ROLE_ADMIN)) {
                return true;
            }
            //admin只有admin才能操作
            if ($subject->getRole()->isRole(Role::ROLE_ADMIN) && $nowUser->getRole()->isRole(Role::ROLE_ADMIN)) {
                return true;
            }
        }
        return false;
    }
}