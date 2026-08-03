<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Validation\Constraint;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraint;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalExternalId extends Constraint
{
    public const NO_VALID_EXTERNAL_ID = 'c4d1d3a0-7e2f-4b7a-9b5d-4f99eecb6e21';

    protected const ERROR_NAMES = [
        self::NO_VALID_EXTERNAL_ID => 'NO_VALID_EXTERNAL_ID',
    ];

    public string $message = 'external_id must contain at least one PayPal:* entry.';
}
