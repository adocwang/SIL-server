<?php
namespace AppBundle\Entity;

use ReflectionClass;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/13/17
 * Time: 14:37
 */
class Role implements RoleInterface
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const CENTER_CHAIRMAN = 'CENTER_CHAIRMAN';
    const ROLE_BRANCH_PRESIDENT = 'ROLE_BRANCH_PRESIDENT';
    const ROLE_CHANNEL_MANAGER = 'ROLE_CHANNEL_MANAGER';
    const ROLE_END_PRESIDENT = 'ROLE_END_PRESIDENT';
    const ROLE_END_PRESIDENT_WITH_CM = 'ROLE_END_PRESIDENT_WITH_CM';
    const ROLE_CUSTOMER_MANAGER = 'ROLE_CUSTOMER_MANAGER';
    const ROLE_PRESIDENT = 'ROLE_PRESIDENT';

    public static $roleName = [
        'ROLE_ADMIN' => '管理员',
        'CENTER_CHAIRMAN' => '中心主任',
        'ROLE_BRANCH_PRESIDENT' => '分行行长',
        'ROLE_CHANNEL_MANAGER' => '渠道管理员',
        'ROLE_END_PRESIDENT' => '支行行长',
        'ROLE_END_PRESIDENT_WITH_CM' => '支行行长兼客户经理',
        'ROLE_CUSTOMER_MANAGER' => '客户经理'
    ];

    private static $roleHierarchy = [
        'ROLE_PRESIDENT' => ['ROLE_BRANCH_PRESIDENT', 'ROLE_END_PRESIDENT', 'ROLE_END_PRESIDENT_WITH_CM'],
        'ROLE_END_PRESIDENT' => ['ROLE_END_PRESIDENT', 'ROLE_END_PRESIDENT_WITH_CM'],
        'ROLE_CUSTOMER_MANAGER' => ['ROLE_CUSTOMER_MANAGER', 'ROLE_END_PRESIDENT_WITH_CM'],
    ];

    private $roleValue = 'ROLE_CUSTOMER_MANAGER';

    public static function getRoleList()
    {
        $list = [];
        foreach (self::$roleName as $value => $name) {
            $list[] = ['value' => $value, 'name' => $name];
        }
        return $list;
    }

    public static function createRole($roleEnName)
    {
        $role = new self();
        if (!$role->setRole($roleEnName)) {
            return null;
        }
        return $role;
    }

    public static function createRoleByName($roleName)
    {
        $role = new self();
        if (!$role->setRoleByName($roleName)) {
            return null;
        }
        return $role;
    }

    /**
     * Returns the role.
     *
     * This method returns a string representation whenever possible.
     *
     * When the role cannot be represented with sufficient precision by a
     * string, it should return null.
     *
     * @return string|null A string representation of the role, or null
     */
    public function getRole()
    {
        $reflect = new ReflectionClass($this);
        $constants = $reflect->getConstants();
        return array_search($this->roleValue, $constants);
    }

    /**
     * @return int
     */
    public function getRoleValue()
    {
        return $this->roleValue;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::$roleName[$this->roleValue];
    }

    /**
     * @param $initial_value string
     * @return bool
     */
    public function setRoleByName($initial_value)
    {
        if (in_array($initial_value, self::$roleName)) {
            $this->roleValue = array_search($initial_value, self::$roleName);
            return true;
        }
        return false;
    }

    /**
     * @param $initial_value integer
     * @return bool
     */
    public function setRole($initial_value)
    {
        $reflect = new ReflectionClass($this);
        $constants = $reflect->getConstants();
        if (in_array($initial_value, $constants)) {
            $this->roleValue = $initial_value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $testRole integer
     * @return bool
     */
    public function isRole($testRole)
    {
        if (!empty(self::$roleHierarchy[$testRole])) {
            return in_array($this->roleValue, self::$roleHierarchy[$testRole]);
        } else {
            return ($this->roleValue == $testRole);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getRole();
    }
}