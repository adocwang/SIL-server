<?php
/**
 * Created by PhpStorm.
 * User: wangyibo
 * Date: 1/31/17
 * Time: 11:37
 */

namespace AppBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ContainsPhoneValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param string $value The value that should be validated
     * @param ContainsPhone|Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!preg_match('/^1[0-9]{10}$/', $value, $matches)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}