<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
enum WebhookRegistrationStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Missing = 'missing';
    case Disabled = 'disabled';
}
