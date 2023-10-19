<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsValidOwnerValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function validate($value, Constraint $constraint)
    {
        assert($constraint instanceof IsValidOwner);

        if (null === $value || '' === $value) {
            return;
        }

        assert($value instanceof User);

        $user = $this->security->getUser();
        if (!$user) {
            throw new \LogicException('IsOwnerValidator should only be used when user is logged in.');
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($value !== $user) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
