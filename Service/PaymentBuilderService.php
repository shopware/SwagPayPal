<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Service;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageStruct;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelStruct;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Api\Payment\ApplicationContext;
use SwagPayPal\PayPal\Api\Payment\Payer;
use SwagPayPal\PayPal\Api\Payment\RedirectUrls;
use SwagPayPal\PayPal\Api\Payment\Transaction;
use SwagPayPal\PayPal\Api\Payment\Transaction\Amount;
use SwagPayPal\PayPal\Api\Payment\Transaction\Amount\Details;

class PaymentBuilderService implements PaymentBuilderInterface
{
    /**
     * @var RepositoryInterface
     */
    private $languageRepo;

    /**
     * @var RepositoryInterface
     */
    private $salesChannelRepo;

    public function __construct(RepositoryInterface $languageRepo, RepositoryInterface $salesChannelRepo)
    {
        $this->languageRepo = $languageRepo;
        $this->salesChannelRepo = $salesChannelRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment(PaymentTransactionStruct $paymentTransaction, Context $context): Payment
    {
        $requestPayment = new Payment();
        $requestPayment->setIntent('sale');

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl($paymentTransaction->getReturnUrl() . '&cancel=1');
        $redirectUrls->setReturnUrl($paymentTransaction->getReturnUrl());

        $amount = new Amount();
        $amount->setTotal($this->formatPrice($paymentTransaction->getAmount()->getTotalPrice()));
        $amount->setCurrency($paymentTransaction->getOrder()->getCurrency()->getShortName());
        $amount->setDetails($this->getAmountDetails($paymentTransaction));

        $transaction = new Transaction();
        $transaction->setAmount($amount);

        $requestPayment->setPayer($payer);
        $requestPayment->setRedirectUrls($redirectUrls);
        $requestPayment->setTransactions([$transaction]);

        $applicationContext = $this->getApplicationContext($context);

        $requestPayment->setApplicationContext($applicationContext);

        return $requestPayment;
    }

    private function getAmountDetails(PaymentTransactionStruct $paymentTransaction): Details
    {
        $amountDetails = new Details();

        $amountDetails->setShipping($this->formatPrice($paymentTransaction->getOrder()->getShippingTotal()));
        $totalAmount = $paymentTransaction->getAmount()->getTotalPrice();
        $taxAmount = $paymentTransaction->getAmount()->getCalculatedTaxes()->getAmount();
        $amountDetails->setSubtotal($this->formatPrice($totalAmount - $taxAmount));
        $amountDetails->setTax($this->formatPrice($taxAmount));

        return $amountDetails;
    }

    /**
     * @return ApplicationContext
     */
    private function getApplicationContext(Context $context): ApplicationContext
    {
        $languageId = $context->getLanguageId();
        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepo->read(new ReadCriteria([$languageId]), $context);
        /** @var LanguageStruct $language */
        $language = $languageCollection->get($languageId);

        $applicationContext = new ApplicationContext();
        $applicationContext->setLocale($language->getLocale()->getCode());

        $brandName = '';

        $salesChannelId = $context->getSourceContext()->getSalesChannelId();
        if ($salesChannelId !== null) {
            /** @var SalesChannelCollection $salesChannelCollection */
            $salesChannelCollection = $this->salesChannelRepo->read(new ReadCriteria([$salesChannelId]), $context);
            /** @var SalesChannelStruct $salesChannel */
            $salesChannel = $salesChannelCollection->get($salesChannelId);
            if ($salesChannel !== null) {
                $brandName = $salesChannel->getName();
            }
        }

        $applicationContext->setBrandName($brandName);

        return $applicationContext;
    }

    private function formatPrice(float $price): string
    {
        return (string) round($price, 2);
    }
}
