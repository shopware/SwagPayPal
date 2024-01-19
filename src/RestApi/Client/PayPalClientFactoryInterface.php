<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PartnerAttributionId;

#[Package('checkout')]
interface PayPalClientFactoryInterface
{
    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClientInterface;
}
