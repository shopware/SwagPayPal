<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Handler;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Exception\CurrencyNotFoundException;
use Swag\PayPal\Payment\Patch\AmountPatchBuilder;
use Swag\PayPal\Payment\Patch\ItemListPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EcsSpbHandler extends AbstractPaymentHandler
{
    public const PAYPAL_PAYMENT_ID_INPUT_NAME = 'paypalPaymentId';
    public const PAYPAL_PAYER_ID_INPUT_NAME = 'paypalPayerId';

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var AmountPatchBuilder
     */
    private $amountPatchBuilder;

    /**
     * @var ItemListPatchBuilder
     */
    private $itemListPatchBuilder;

    /**
     * @var ShippingAddressPatchBuilder
     */
    private $shippingAddressPatchBuilder;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        PaymentResource $paymentResource,
        EntityRepositoryInterface $orderTransactionRepo,
        SettingsServiceInterface $settingsService,
        AmountPatchBuilder $amountPatchBuilder,
        ItemListPatchBuilder $itemListPatchBuilder,
        ShippingAddressPatchBuilder $shippingAddressPatchBuilder,
        EntityRepositoryInterface $currencyRepository
    ) {
        parent::__construct($paymentResource, $orderTransactionRepo);
        $this->settingsService = $settingsService;
        $this->amountPatchBuilder = $amountPatchBuilder;
        $this->itemListPatchBuilder = $itemListPatchBuilder;
        $this->shippingAddressPatchBuilder = $shippingAddressPatchBuilder;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @throws AddressNotFoundException
     * @throws AsyncPaymentProcessException
     * @throws CurrencyNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidOrderException
     * @throws PayPalSettingsInvalidException
     */
    public function handleEcsPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): RedirectResponse {
        $paypalPaymentId = $dataBag->get(self::PAYPAL_PAYMENT_ID_INPUT_NAME);
        $payerId = $dataBag->get(self::PAYPAL_PAYER_ID_INPUT_NAME);

        $this->addPayPalTransactionId($transaction, $paypalPaymentId, $salesChannelContext->getContext());

        $order = $transaction->getOrder();
        $currencyEntity = $order->getCurrency();
        if ($currencyEntity === null) {
            $currencyEntity = $this->getCurrency($order->getCurrencyId(), $salesChannelContext->getContext());
        }

        $currency = $currencyEntity->getIsoCode();
        $orderTransaction = $transaction->getOrderTransaction();

        $patches = [
            $this->shippingAddressPatchBuilder->createShippingAddressPatch($customer),
            $this->amountPatchBuilder->createAmountPatch(
                $orderTransaction->getAmount(),
                $order->getShippingCosts()->getTotalPrice(),
                $currency
            ),
        ];

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        if ($this->settingsService->getSettings($salesChannelId)->getEcsSubmitCart()) {
            $patches[] = $this->itemListPatchBuilder->createItemListPatch($order, $currency);
        }

        $this->patchPayPalPayment($patches, $paypalPaymentId, $salesChannelId, $orderTransaction->getId());

        return $this->createResponse(
            $transaction->getReturnUrl(),
            $paypalPaymentId,
            $payerId,
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID
        );
    }

    public function handleSpbPayment(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $paypalPaymentId = $dataBag->get(self::PAYPAL_PAYMENT_ID_INPUT_NAME);
        $payerId = $dataBag->get(self::PAYPAL_PAYER_ID_INPUT_NAME);
        $this->addPayPalTransactionId($transaction, $paypalPaymentId, $salesChannelContext->getContext());

        return $this->createResponse(
            $transaction->getReturnUrl(),
            $paypalPaymentId,
            $payerId,
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID
        );
    }

    private function createResponse(
        string $returnUrl,
        string $paypalPaymentId,
        string $payerId,
        string $payPalType
    ): RedirectResponse {
        $parameters = http_build_query([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => $paypalPaymentId,
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => $payerId,
            $payPalType => true,
        ]);

        return new RedirectResponse(sprintf('%s&%s', $returnUrl, $parameters));
    }

    /**
     * @throws CurrencyNotFoundException
     * @throws InconsistentCriteriaIdsException
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
}
