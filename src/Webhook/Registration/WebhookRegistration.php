<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('checkout')]
final class WebhookRegistration extends Struct
{
    /**
     * @param bool $outdated whether the registered URL is one of the expected ones, but with another token
     */
    public function __construct(
        public readonly WebhookRegistrationStatus $status,
        public readonly ?string $registeredUrl = null,
        public readonly bool $outdated = false,
    ) {
    }
}
