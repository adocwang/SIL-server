<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 11:40
 */

namespace AppBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContainsPhone extends Constraint
{
    public $message = '`%string%` 不是合法的手机号码';

    public function validatedBy()
    {
        return get_class($this) . 'Validator';
    }
}