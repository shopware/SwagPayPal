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
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Struct\ApplePayCheckoutData;
use Swag\PayPal\Util\Lifecycle\Method\ApplePayMethodData;

#[Package('checkout')]
class ApplePayCheckoutDataService extends AbstractCheckoutDataService
{
    public function buildCheckoutData(SalesChannelContext $context, ?Cart $cart = null, ?OrderEntity $order = null): ?ApplePayCheckoutData
    {
        return (new ApplePayCheckoutData())->assign($this->getBaseData($context, $order))->assign([
            'totalPrice' => $this->formatPrice($order?->getPrice()->getTotalPrice() ?? $cart?->getPrice()->getTotalPrice() ?? 0),
            'brandName' => $this->getBrandName($context),
            'billingAddress' => $this->getBillingAddress($order, $context),
        ]);
    }

    public function getMethodDataClass(): string
    {
        return ApplePayMethodData::class;
    }

    private function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = $this->systemConfigService->getString(Settings::BRAND_NAME, $salesChannelContext->getSalesChannelId());

        if ($brandName === '') {
            $brandName = $salesChannelContext->getSalesChannel()->getName() ?? '';
        }

        return $brandName;
    }

    private function formatPrice(float $price): string
    {
        return \number_format(\round($price, 2), 2, '.', '');
    }

    private function getBillingAddress(?OrderEntity $order, SalesChannelContext $context): array
    {
        $address = $order?->getBillingAddress() ?? $context->getCustomer()?->getActiveBillingAddress();

        return [
            'addressLines' => $address?->getStreet(),
            'administrativeArea' => $address?->getCountryState()?->getName(),
            'country' => $address?->getCountry()?->getIso3(),
            'countryCode' => $address?->getCountry()?->getIso(),
            'familyName' => $address?->getLastName(),
            'givenName' => $address?->getFirstName(),
            'locality' => $address?->getCity(),
            'postalCode' => $address?->getZipcode(),
        ];
    }
}
