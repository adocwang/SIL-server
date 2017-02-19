<?php
namespace AppBundle\Constant;

use ReflectionClass;
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 2/13/17
 * Time: 14:37
 */
class State
{
    const STATE_UN_ACTIVE = 0;
    const STATE_NORMAL = 1;
    const STATE_FREEZED = 2;
    const STATE_DELETED = 3;
    const STATE_BLOCKED = 4;

    public static $stateName = [
        0 => '未激活',
        1 => '正常',
        2 => '已冻结',
        3 => '已删除',
        4 => '已阻止',
    ];
}