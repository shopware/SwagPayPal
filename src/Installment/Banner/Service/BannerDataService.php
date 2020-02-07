<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner\Service;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\Product\ProductPage;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;

class BannerDataService
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(PaymentMethodUtil $paymentMethodUtil)
    {
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @param CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage $page
     */
    public function getInstallmentBannerData(
        Page $page,
        SalesChannelContext $salesChannelContext,
        SwagPayPalSettingStruct $settings
    ): BannerData {
        $amount = 0.0;

        if ($page instanceof CheckoutCartPage
            || $page instanceof CheckoutConfirmPage
            || $page instanceof CheckoutRegisterPage
            || $page instanceof OffcanvasCartPage
        ) {
            $amount = $page->getCart()->getPrice()->getTotalPrice();
        }

        if ($page instanceof ProductPage) {
            $product = $page->getProduct();

            $amount = $product->getCalculatedPrice()->getUnitPrice();

            $firstCalculatedPrice = $product->getCalculatedPrices()->first();
            if ($firstCalculatedPrice !== null) {
                $amount = $firstCalculatedPrice->getUnitPrice();
            }
        }

        $currency = $salesChannelContext->getCurrency()->getIsoCode();

        return new BannerData(
            (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            $settings->getClientId(),
            $amount,
            $currency
        );
    }
}
