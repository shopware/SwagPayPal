<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\RepositoryNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageCollection;
use Shopware\Core\Framework\Language\LanguageDefinition;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\Api\Payment\Payer;
use Swag\PayPal\PayPal\Api\Payment\RedirectUrls;
use Swag\PayPal\PayPal\Api\Payment\Transaction;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount\Details;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\PayPal\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\Exception\PayPalSettingsNotFoundException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralEntity;

class PaymentBuilderService implements PaymentBuilderInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepo;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepo;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsProvider;

    /**
     * @var SwagPayPalSettingGeneralEntity
     */
    private $settings;

    /**
     * @throws RepositoryNotFoundException
     */
    public function __construct(
        DefinitionRegistry $definitionRegistry,
        SettingsServiceInterface $settingsProvider
    ) {
        $this->languageRepo = $definitionRegistry->getRepository(LanguageDefinition::getEntityName());
        $this->salesChannelRepo = $definitionRegistry->getRepository(SalesChannelDefinition::getEntityName());
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidOrderException
     * @throws PayPalSettingsInvalidException
     * @throws PayPalSettingsNotFoundException
     */
    public function getPayment(AsyncPaymentTransactionStruct $paymentTransaction, SalesChannelContext $salesChannelContext): Payment
    {
        $this->settings = $this->settingsProvider->getSettings($salesChannelContext->getContext());

        $intent = $this->getIntent();
        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($paymentTransaction->getReturnUrl());
        $transaction = $this->createTransaction($paymentTransaction);
        $applicationContext = $this->getApplicationContext($salesChannelContext);

        $requestPayment = new Payment();
        $requestPayment->setIntent($intent);
        $requestPayment->setPayer($payer);
        $requestPayment->setRedirectUrls($redirectUrls);
        $requestPayment->setTransactions([$transaction]);
        $requestPayment->setApplicationContext($applicationContext);

        return $requestPayment;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function getIntent(): string
    {
        $intent = $this->settings->getIntent();
        $this->validateIntent($intent);

        return $intent;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function validateIntent(string $intent): void
    {
        if (!\in_array($intent, PaymentIntent::INTENTS, true)) {
            throw new PayPalSettingsInvalidException('intent');
        }
    }

    private function createPayer(): Payer
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        return $payer;
    }

    private function createRedirectUrls(string $returnUrl): RedirectUrls
    {
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl(sprintf('%s&cancel=1', $returnUrl));
        $redirectUrls->setReturnUrl($returnUrl);

        return $redirectUrls;
    }

    /**
     * @throws InvalidOrderException
     */
    private function createTransaction(AsyncPaymentTransactionStruct $paymentTransaction): Transaction
    {
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $order = $paymentTransaction->getOrder();

        $orderTransactionAmount = $orderTransaction->getAmount();
        $currency = (string) $order->getCurrency()->getShortName();

        $transaction = new Transaction();

        $amount = $this->createAmount($orderTransactionAmount, $order, $currency);
        $transaction->setAmount($amount);

        if ($this->settings->getSendOrderNumber()) {
            $orderNumberPrefix = (string) $this->settings->getOrderNumberPrefix();
            $orderNumber = $orderNumberPrefix . $order->getOrderNumber();
            $transaction->setInvoiceNumber($orderNumber);
        }

        if ($this->settings->getSubmitCart()) {
            $items = $this->getItemList($order, $currency);

            if (!empty($items)) {
                $itemList = new ItemList();
                $itemList->setItems($items);
                $transaction->setItemList($itemList);
            }
        }

        return $transaction;
    }

    private function createAmount(CalculatedPrice $orderTransactionAmount, OrderEntity $order, string $currency): Amount
    {
        $amount = new Amount();
        $amount->setTotal($this->formatPrice($orderTransactionAmount->getTotalPrice()));
        $amount->setCurrency($currency);
        $amount->setDetails($this->getAmountDetails($order, $orderTransactionAmount));

        return $amount;
    }

    private function getAmountDetails(OrderEntity $order, CalculatedPrice $orderTransactionAmount): Details
    {
        $amountDetails = new Details();

        $amountDetails->setShipping(
            $this->formatPrice($order->getShippingCosts()->getTotalPrice())
        );
        $totalAmount = $orderTransactionAmount->getTotalPrice();
        $taxAmount = $orderTransactionAmount->getCalculatedTaxes()->getAmount();
        $amountDetails->setSubtotal($this->formatPrice($totalAmount - $taxAmount));
        $amountDetails->setTax($this->formatPrice($taxAmount));

        return $amountDetails;
    }

    /**
     * @throws InvalidOrderException
     *
     * @return Item[]
     */
    private function getItemList(OrderEntity $order, string $currency): array
    {
        $items = [];
        if ($order->getLineItems() === null) {
            throw new InvalidOrderException($order->getId());
        }

        /** @var OrderLineItemEntity[] $lineItems */
        $lineItems = $order->getLineItems()->getElements();

        foreach ($lineItems as $id => $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                return [];
            }

            $items[] = $this->createItemFromLineItem($lineItem, $currency, $price);
        }

        return $items;
    }

    private function createItemFromLineItem(
        OrderLineItemEntity $lineItem,
        string $currency,
        CalculatedPrice $price
    ): Item {
        $taxAmount = $price->getCalculatedTaxes()->getAmount();

        $item = new Item();
        $item->setName($lineItem->getLabel());
        $item->setSku($lineItem->getPayload()['id']);
        $item->setPrice($this->formatPrice($price->getTotalPrice() - $taxAmount));
        $item->setCurrency($currency);
        $item->setQuantity($lineItem->getQuantity());
        $item->setTax($this->formatPrice($taxAmount));

        return $item;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getApplicationContext(SalesChannelContext $salesChannelContext): ApplicationContext
    {
        $applicationContext = new ApplicationContext();
        $applicationContext->setLocale($this->getLocaleCode($salesChannelContext->getContext()));
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext));
        $applicationContext->setLandingPage($this->getLandingPageType());

        return $applicationContext;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getLocaleCode(Context $context): string
    {
        $languageId = $context->getLanguageId();
        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepo->search(new Criteria([$languageId]), $context);
        /** @var LanguageEntity $language */
        $language = $languageCollection->get($languageId);

        return $language->getLocale()->getCode();
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = $this->settings->getBrandName();

        if ($brandName === null || $brandName === '') {
            $brandName = $this->useSalesChannelNameAsBrandName($salesChannelContext);
        }

        return $brandName;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function useSalesChannelNameAsBrandName(SalesChannelContext $salesChannelContext): string
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        /** @var SalesChannelCollection $salesChannelCollection */
        $salesChannelCollection = $this->salesChannelRepo->search(
            new Criteria([$salesChannelId]),
            $salesChannelContext->getContext()
        );
        $salesChannel = $salesChannelCollection->get($salesChannelId);

        $brandName = '';
        if ($salesChannel !== null) {
            $salesChannelName = $salesChannel->getName();
            if ($salesChannelName !== null) {
                $brandName = $salesChannelName;
            }
        }

        return $brandName;
    }

    private function getLandingPageType(): string
    {
        $landingPageType = $this->settings->getLandingPage();
        if ($landingPageType !== ApplicationContext::LANDINGPAGE_TYPE_BILLING) {
            $landingPageType = ApplicationContext::LANDINGPAGE_TYPE_LOGIN;
        }

        return $landingPageType;
    }

    private function formatPrice(float $price): string
    {
        return (string) round($price, 2);
    }
}
