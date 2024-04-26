<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Storefront\Data\Struct\GooglePayCheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\GooglePayMethodData;

#[Package('checkout')]
class GooglePayCheckoutDataService extends AbstractCheckoutDataService
{
    public function buildCheckoutData(SalesChannelContext $context, ?Cart $cart = null, ?OrderEntity $order = null): ?GooglePayCheckoutData
    {
        return (new GooglePayCheckoutData())->assign($this->getBaseData($context, $order))->assign([
            'totalPrice' => $this->formatPrice($order?->getPrice()->getTotalPrice() ?? $cart?->getPrice()->getTotalPrice() ?? 0),
            'sandbox' => $this->credentialsUtil->isSandbox($context->getSalesChannelId()),
            'displayItems' => [[
                'label' => 'Subtotal',
                'price' => $this->formatPrice($order?->getPrice()->getNetPrice() ?? $cart?->getPrice()->getNetPrice() ?? 0),
                'type' => 'SUBTOTAL',
            ], [
                'label' => 'Tax',
                'price' => $this->formatPrice($order?->getPrice()->getCalculatedTaxes()->getAmount() ?? $cart?->getPrice()->getCalculatedTaxes()->getAmount() ?? 0),
                'type' => 'TAX',
            ]],
        ]);
    }

    public function getMethodDataClass(): string
    {
        return GooglePayMethodData::class;
    }

    private function formatPrice(float $price): string
    {
        return \number_format(\round($price, 2), 2, '.', '');
    }
}
