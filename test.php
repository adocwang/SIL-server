<?php

/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/13/17
 * Time: 14:48
 */
class Role
{
    const __default = self::ROLE_CUSTOMER_MANAGER;
    const ROLE_ADMIN = 1;
    const ROLE_BRANCH_PRESIDENT = 2;
    const ROLE_END_PRESIDENT = 3;
    const ROLE_CUSTOMER_MANAGER = 4;

    public static $roleName = [
        1 => '管理员',
        2 => '分行行长',
        3 => '支行行长',
        4 => '客户经理',
    ];

    public static function getRole()
    {
        $reflect = new ReflectionClass(self());
        return $reflect->getConstants();

    }
}

print_r(Role::getRole());