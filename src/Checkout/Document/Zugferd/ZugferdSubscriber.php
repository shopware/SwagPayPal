<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Document\Zugferd;

use horstoeko\zugferd\codelists\ZugferdPaymentMeans;
use Shopware\Core\Checkout\Document\Zugferd\ZugferdInvoiceGeneratedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ZugferdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ZugferdInvoiceGeneratedEvent::class => 'generateInvoice',
        ];
    }

    public function generateInvoice(ZugferdInvoiceGeneratedEvent $event): void
    {
        $transaction = $event->order->getTransactions()?->last();
        // Method is not available for version 6.7.0.0
        /** @phpstan-ignore function.alreadyNarrowedType */
        if (Feature::isActive('v6.8.0.0') && \method_exists($event->order, 'getPrimaryOrderTransaction')) {
            /** @var OrderTransactionEntity|null $transaction - silence 6.7.0.0 type errors */
            $transaction = $event->order->getPrimaryOrderTransaction();
        }

        $paymentMethod = $transaction?->getPaymentMethod();
        if ($paymentMethod === null || !str_starts_with($paymentMethod->getTechnicalName(), 'swag_paypal_')) {
            return;
        }

        $locale = $event->order->getLanguage()?->getLocale()?->getCode();
        $paymentMeans = [
            'typeCode' => ZugferdPaymentMeans::UNTDID_4461_ZZZ,
            'information' => $this->translator->trans('paypal.e-invoice.paymentMethod', ['%paymentMethod%' => $paymentMethod->getTranslation('name')], locale: $locale),
        ];

        if ($paymentMethod->getTechnicalName() === 'swag_paypal_pui') {
            $values = $transaction->getTranslatedCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION)['deposit_bank_details'] ?? [];
            $ratePay = $this->translator->trans('paypal.payUponInvoice.document.paymentNoteRatepay', ['%companyName%' => $event->config->getCompanyName()], locale: $locale);

            $paymentMeans['information'] .= ' | ' . $ratePay;
            $paymentMeans['typeCode'] = ZugferdPaymentMeans::UNTDID_4461_42;
            $paymentMeans['payeeIban'] = $values['iban'] ?? null;
            $paymentMeans['payeeAccountName'] = $values['account_holder_name'] ?? null;
            $paymentMeans['payeeBic'] = $values['bic'] ?? null;
        } else {
            $paymentMeans['information'] .= ' | ' . $this->translator->trans('paypal.e-invoice.orderId', ['%orderId%' => $transaction->getTranslatedCustomFieldsValue('swag_paypal_order_id')], locale: $locale);
        }

        $event->document->getBuilder()
            ->addDocumentPaymentMean(...$paymentMeans);
    }
}
