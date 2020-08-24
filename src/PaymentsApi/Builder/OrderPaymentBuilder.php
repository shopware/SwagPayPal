<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\CurrencyNotFoundException;
use Swag\PayPal\PaymentsApi\Builder\Util\AmountProvider;
use Swag\PayPal\PaymentsApi\Builder\Util\ItemListProvider;
use Swag\PayPal\PaymentsApi\Service\TransactionValidator;
use Swag\PayPal\PayPal\ApiV1\Api\Payment;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;

class OrderPaymentBuilder extends AbstractPaymentBuilder implements OrderPaymentBuilderInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        SettingsServiceInterface $settingsService,
        LocaleCodeProvider $localeCodeProvider,
        EntityRepositoryInterface $currencyRepository,
        PriceFormatter $priceFormatter
    ) {
        parent::__construct($settingsService, $localeCodeProvider, $priceFormatter);
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext
    ): Payment {
        $this->settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $intent = $this->getIntent();
        $payer = $this->createPayer();
        $redirectUrls = $this->createRedirectUrls($paymentTransaction->getReturnUrl());
        $transaction = $this->createTransaction($paymentTransaction, $salesChannelContext->getContext());
        $applicationContext = $this->getApplicationContext($salesChannelContext);

        $requestPayment = new Payment();
        $requestPayment->setIntent($intent);
        $requestPayment->setPayer($payer);
        $requestPayment->setRedirectUrls($redirectUrls);
        $requestPayment->setTransactions([$transaction]);
        $requestPayment->setApplicationContext($applicationContext);

        return $requestPayment;
    }

    private function createTransaction(
        AsyncPaymentTransactionStruct $paymentTransaction,
        Context $context
    ): Transaction {
        $orderTransaction = $paymentTransaction->getOrderTransaction();
        $order = $paymentTransaction->getOrder();

        $orderTransactionAmount = $orderTransaction->getAmount();

        $currencyEntity = $order->getCurrency();
        if ($currencyEntity === null) {
            $currencyEntity = $this->getCurrency($order->getCurrencyId(), $context);
        }

        $currency = $currencyEntity->getIsoCode();

        $transaction = new Transaction();

        $amount = (new AmountProvider($this->priceFormatter))->createAmount(
            $orderTransactionAmount,
            $order->getShippingCosts()->getTotalPrice(),
            $currency
        );
        $transaction->setAmount($amount);

        if ($this->settings->getSendOrderNumber()) {
            $orderNumberPrefix = (string) $this->settings->getOrderNumberPrefix();
            $orderNumber = $orderNumberPrefix . $order->getOrderNumber();
            $transaction->setInvoiceNumber($orderNumber);
        }

        $itemListValid = true;
        if ($this->settings->getSubmitCart()) {
            $items = (new ItemListProvider($this->priceFormatter))->getItemList($order, $currency);

            if (!empty($items)) {
                $itemList = new ItemList();
                $itemList->setItems($items);
                $transaction->setItemList($itemList);
            }
            $itemListValid = TransactionValidator::validateItemList([$transaction]);
        }

        if ($itemListValid === false) {
            $transaction->setItemList(null);
        }

        return $transaction;
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
}
