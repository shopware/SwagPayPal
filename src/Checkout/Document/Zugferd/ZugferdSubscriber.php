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
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ZugferdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CredentialsUtil $credentials,
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
        $paymentMethod = $transaction?->getPaymentMethod();
        if ($paymentMethod === null || !str_starts_with($paymentMethod->getTechnicalName(), 'swag_paypal_')) {
            return;
        }

        $locale = $event->order->getLanguage()?->getLocale()?->getCode();
        if ($paymentMethod->getTechnicalName() === 'swag_paypal_pui') {
            $paymentMeans = $this->pui($transaction, $locale, $event->config->getCompanyName());
        } else {
            $paymentSnippet = $this->translator->trans('paypal.e-invoice.paymentMethod', ['%paymentMethod%' => $transaction->getPaymentMethod()?->getName()], locale: $locale);
            $orderId = $this->translator->trans('paypal.e-invoice.orderId', ['%orderId%' => $transaction->getCustomFieldsValue('swag_paypal_order_id')], locale: $locale);
            $merchantId = $this->translator->trans('paypal.e-invoice.merchantId', ['%merchantId%' => $this->credentials->getMerchantPayerId($event->order->getSalesChannelId())], locale: $locale);

            $paymentMeans = [
                'typeCode' => ZugferdPaymentMeans::UNTDID_4461_ZZZ,
                'information' => implode(' | ', [$paymentSnippet, $orderId, $merchantId]),
            ];
        }

        $event->document->getBuilder()
            ->addDocumentPaymentMean(...$paymentMeans);
    }

    private function pui(OrderTransactionEntity $transaction, ?string $locale, ?string $companyName): array
    {
        $values = $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION)['deposit_bank_details'] ?? [];

        $paymentSnippet = $this->translator->trans('paypal.e-invoice.paymentMethod', ['%paymentMethod%' => $transaction->getPaymentMethod()?->getName()], locale: $locale);
        $ratePay = $this->translator->trans('paypal.payUponInvoice.document.paymentNoteRatepay', ['%companyName%' => $companyName], locale: $locale);

        return [
            'typeCode' => ZugferdPaymentMeans::UNTDID_4461_42,
            'information' => $paymentSnippet . ' | ' . $ratePay,
            'payeeIban' => $values['iban'] ?? null,
            'payeeAccountName' => $values['account_holder_name'] ?? null,
            'payeeBic' => $values['bic'] ?? null,
        ];
    }
}
