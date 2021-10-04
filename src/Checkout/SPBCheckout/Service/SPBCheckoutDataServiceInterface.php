<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutButtonData;

interface SPBCheckoutDataServiceInterface
{
    public function buildCheckoutData(SalesChannelContext $context, ?string $orderId = null): SPBCheckoutButtonData;

    /**
     * @return string[]
     */
    public function getDisabledAlternativePaymentMethods(float $totalPrice, string $currencyIsoCode): array;
}
