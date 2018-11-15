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
use SwagPayPal\PayPal\Struct\Payment;
use SwagPayPal\PayPal\Struct\Payment\ApplicationContext;
use SwagPayPal\PayPal\Struct\Payment\Payer;
use SwagPayPal\PayPal\Struct\Payment\RedirectUrls;
use SwagPayPal\PayPal\Struct\Payment\Transactions;
use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;
use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount\Details;

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
        $requestParameters = new Payment();
        $requestParameters->setIntent('sale');

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl($paymentTransaction->getReturnUrl() . '&cancel=1');
        $redirectUrls->setReturnUrl($paymentTransaction->getReturnUrl());

        $amount = new Amount();
        $amount->setDetails($this->getAmountDetails($paymentTransaction));
        $amount->setCurrency($paymentTransaction->getOrder()->getCurrency()->getShortName());
        $amount->setTotal($paymentTransaction->getAmount()->getTotalPrice());

        $transactions = new Transactions();
        $transactions->setAmount($amount);

        $requestParameters->setPayer($payer);
        $requestParameters->setRedirectUrls($redirectUrls);
        $requestParameters->setTransactions($transactions);

        $applicationContext = $this->getApplicationContext($context);

        $requestParameters->setApplicationContext($applicationContext);

        return $requestParameters;
    }

    private function getAmountDetails(PaymentTransactionStruct $paymentTransaction): Details
    {
        $amountDetails = new Details();

        $amountDetails->setShipping($paymentTransaction->getOrder()->getShippingTotal());
        $totalAmount = $paymentTransaction->getAmount()->getTotalPrice();
        $taxAmount = $paymentTransaction->getAmount()->getCalculatedTaxes()->getAmount();
        $amountDetails->setSubTotal($totalAmount - $taxAmount);
        $amountDetails->setTax($taxAmount);

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
}
