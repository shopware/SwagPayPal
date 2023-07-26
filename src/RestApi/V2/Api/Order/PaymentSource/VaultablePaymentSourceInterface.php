<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;

#[Package('checkout')]
interface VaultablePaymentSourceInterface
{
    public function getAttributes(): ?Attributes;

    public function setAttributes(?Attributes $attributes): void;

    public function getVaultIdentifier(): string;
}
