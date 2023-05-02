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
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Util\PaymentMethodUtil;

class BannerDataService implements BannerDataServiceInterface
{
    private PaymentMethodUtil $paymentMethodUtil;

    private CredentialsUtilInterface $credentialsUtil;

    /**
     * @internal
     */
    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        CredentialsUtilInterface $credentialsUtil
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->credentialsUtil = $credentialsUtil;
    }

    /**
     * @param CheckoutCartPage|CheckoutConfirmPage|CheckoutRegisterPage|OffcanvasCartPage|ProductPage|FooterPagelet|QuickviewPagelet $page
     */
    public function getInstallmentBannerData(
        $page,
        SalesChannelContext $salesChannelContext
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

        $bannerData = new BannerData(
            (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext()),
            $this->credentialsUtil->getClientId($salesChannelContext->getSalesChannelId()),
            $amount,
            $salesChannelContext->getCurrency()->getIsoCode()
        );

        $merchantPayerId = $this->credentialsUtil->getMerchantPayerId($salesChannelContext->getSalesChannelId());

        $bannerData->assign([
            'merchantPayerId' => $merchantPayerId,
            'partnerAttributionId' => $merchantPayerId ? PartnerAttributionId::PAYPAL_PPCP : PartnerAttributionId::PAYPAL_CLASSIC,
        ]);

        return $bannerData;
    }
}
