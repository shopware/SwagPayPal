<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Validation\Constraint;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalExternalIdValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PayPalExternalId) {
            throw new UnexpectedTypeException($constraint, PayPalExternalId::class);
        }

        if (!\is_array($value)) {
            return;
        }

        foreach ($value as $entry) {
            if (\is_string($entry) && str_starts_with($entry, 'PayPal:')) {
                return;
            }
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setCode(PayPalExternalId::NO_VALID_EXTERNAL_ID)
            ->addViolation();
    }
}
