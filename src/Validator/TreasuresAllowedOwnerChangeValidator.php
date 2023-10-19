<?php

namespace App\Validator;

use App\Entity\DragonTreasure;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TreasuresAllowedOwnerChangeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function validate($value, Constraint $constraint)
    {
        assert($constraint instanceof TreasuresAllowedOwnerChange);

        if (null === $value || '' === $value) {
            return;
        }

        assert($value instanceof Collection);

        $unitOfWork = $this->entityManager->getUnitOfWork();
        foreach ($value as $dragonTreasure) {
            assert($dragonTreasure instanceof DragonTreasure);

            $originData = $unitOfWork->getOriginalEntityData($dragonTreasure);
            $originalOwnerId = $originData['owner_id'];
            $newOwnerId = $dragonTreasure->getOwner()->getId();

            if (!$originalOwnerId || $originalOwnerId === $newOwnerId) {
                return;
            }

            $this->context->buildViolation($constraint->message)
                ->addViolation();

        }
    }
}
