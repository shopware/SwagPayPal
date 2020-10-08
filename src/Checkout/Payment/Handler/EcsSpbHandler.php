<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Handler;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\CurrencyNotFoundException;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Patch\AmountPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\ShippingNamePatchBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EcsSpbHandler extends AbstractPaymentHandler
{
    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    /**
     * @var ShippingAddressPatchBuilder
     */
    private $shippingAddressPatchBuilder;

    /**
     * @var ShippingNamePatchBuilder
     */
    private $shippingNamePatchBuilder;

    /**
     * @var AmountPatchBuilder
     */
    private $amountPatchBuilder;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var ItemListProvider
     */
    private $itemListProvider;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepo,
        SettingsServiceInterface $settingsService,
        EntityRepositoryInterface $currencyRepository,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder,
        ShippingNamePatchBuilder $shippingNamePatchBuilder,
        AmountPatchBuilder $amountPatchBuilder,
        OrderResource $orderResource,
        ItemListProvider $itemListProvider
    ) {
        parent::__construct($orderTransactionRepo);
        $this->settingsService = $settingsService;
        $this->currencyRepository = $currencyRepository;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
        $this->shippingNamePatchBuilder = $shippingNamePatchBuilder;
        $this->amountPatchBuilder = $amountPatchBuilder;
        $this->orderResource = $orderResource;
        $this->itemListProvider = $itemListProvider;
    }

    public function handleEcsPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): RedirectResponse {
        $paypalOrderId = $dataBag->get(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $orderTransaction = $transaction->getOrderTransaction();
        $orderTransactionId = $orderTransaction->getId();

        $this->addPayPalOrderId(
            $orderTransactionId,
            $paypalOrderId,
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT,
            $salesChannelContext->getContext()
        );

        $order = $transaction->getOrder();
        $currency = $order->getCurrency();
        if ($currency === null) {
            $currency = $this->getCurrency($order->getCurrencyId(), $salesChannelContext->getContext());
        }

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $submitCart = $this->settingsService->getSettings($salesChannelId)->getEcsSubmitCart();
        $purchaseUnit = new PurchaseUnit();
        if ($submitCart) {
            $purchaseUnit->setItems($this->itemListProvider->getItemList($currency, $order));
        }

        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->shippingNamePatchBuilder->createShippingNamePatch($customer),
            $this->amountPatchBuilder->createAmountPatch(
                $orderTransaction->getAmount(),
                $order->getShippingCosts(),
                $currency,
                $purchaseUnit
            ),
        ];

        $this->patchPaypalOrder(
            $patches,
            $paypalOrderId,
            $salesChannelId,
            $orderTransactionId,
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT
        );

        return $this->createResponse(
            $transaction->getReturnUrl(),
            $paypalOrderId,
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID
        );
    }

    public function handleSpbPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $paypalOrderId = $dataBag->get(self::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME);
        $this->addPayPalOrderId(
            $transaction->getOrderTransaction()->getId(),
            $paypalOrderId,
            PartnerAttributionId::SMART_PAYMENT_BUTTONS,
            $salesChannelContext->getContext()
        );

        return $this->createResponse(
            $transaction->getReturnUrl(),
            $paypalOrderId,
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID
        );
    }

    private function createResponse(
        string $returnUrl,
        string $paypalOrderId,
        string $payPalType
    ): RedirectResponse {
        $parameters = \http_build_query([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => $paypalOrderId,
            $payPalType => true,
        ]);

        return new RedirectResponse(\sprintf('%s&%s', $returnUrl, $parameters));
    }

    /**
     * @throws CurrencyNotFoundException
     */
    private function getCurrency(string $currencyId, Context $context): CurrencyEntity
    {
        $criteria = new Criteria([$currencyId]);

        /** @var CurrencyCollection $currencyCollection */
        $currencyCollection = $this->currencyRepository->search($criteria, $context);

        $currency = $currencyCollection->get($currencyId);
        if ($currency === null) {
            throw new CurrencyNotFoundException($currencyId);
        }

        return $currency;
    }

    private function patchPaypalOrder(
        array $patches,
        string $paypalOrderId,
        string $salesChannelId,
        string $orderTransactionId,
        string $partnerAttributionId
    ): void {
        try {
            $this->orderResource->update(
                $patches,
                $paypalOrderId,
                $salesChannelId,
                $partnerAttributionId
            );
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $orderTransactionId,
                \sprintf('An error occurred during the communication with PayPal%s%s', PHP_EOL, $e->getMessage())
            );
        }
    }
}
