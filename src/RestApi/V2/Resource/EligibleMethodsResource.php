<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Shopware\PayPalSDK\Struct\V2\EligibleMethodsData;
use Shopware\PayPalSDK\Struct\V2\FindEligibleMethods;
use Shopware\PayPalSDK\Struct\V2\FindEligibleMethods\Customer;
use Shopware\PayPalSDK\Struct\V2\FindEligibleMethods\Preferences;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Amount;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payee;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class EligibleMethodsResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
        private readonly SystemConfigService $systemConfigService,
        private readonly CredentialsUtilInterface $credentialsUtil,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function findEligibleMethods(SalesChannelContext $salesChannelContext): EligibleMethodsData
    {
        $customer = new Customer();
        $customer->setCountryCode($salesChannelContext->getCustomer()?->getActiveBillingAddress()?->getCountry()?->getIso() ?? $salesChannelContext->getShippingLocation()->getCountry()->getIso());

        $preferences = new Preferences();
        $preferences->setPaymentFlow(Preferences::PAYMENT_FLOW_ONE_TIME_PAYMENT);
        $preferences->setIntent($this->systemConfigService->getString(Settings::INTENT, $salesChannelContext->getSalesChannelId()));

        $payee = new Payee();
        $payee->setMerchantId($this->credentialsUtil->getMerchantPayerId($salesChannelContext->getSalesChannelId()));

        $amount = new Amount();
        $amount->setCurrencyCode($salesChannelContext->getCurrency()->getIsoCode());

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setPayee($payee);
        $purchaseUnit->setAmount($amount);

        $findEligibleMethods = new FindEligibleMethods();
        $findEligibleMethods->setCustomer($customer);
        $findEligibleMethods->setPreferences($preferences);
        $findEligibleMethods->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));

        return $this->paymentGateway->findEligibleMethods($findEligibleMethods, $this->apiContextFactory->getApiContext($salesChannelContext->getSalesChannelId()));
    }
}
