<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Document\Zugferd;

use Shopware\Core\Checkout\Document\Zugferd\ZugferdInvoiceGeneratedEvent;
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
    /**
     * Payment to bank account
     */
    private const UNTDID_4461_42 = '42';

    /**
     * Mutually defined
     */
    private const UNTDID_4461_ZZZ = 'ZZZ';

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        if (!\class_exists(ZugferdInvoiceGeneratedEvent::class)) {
            return [];
        }

        return [
            ZugferdInvoiceGeneratedEvent::class => 'generateInvoice',
        ];
    }

    public function generateInvoice(ZugferdInvoiceGeneratedEvent $event): void
    {
        // Method added with v6.6.10.6
        // @phpstan-ignore-next-line
        if (!method_exists($event->document, 'getBuilder')) {
            return;
        }

        $transaction = $event->order->getTransactions()?->last();
        $paymentMethod = $transaction?->getPaymentMethod();
        // @phpstan-ignore-next-line
        if ($paymentMethod === null || !str_starts_with($paymentMethod->getTechnicalName() ?? '', 'swag_paypal_')) {
            return;
        }

        $locale = $event->order->getLanguage()?->getLocale()?->getCode();
        $paymentMeans = [
            'typeCode' => self::UNTDID_4461_ZZZ,
            'information' => $this->translator->trans('paypal.e-invoice.paymentMethod', ['%paymentMethod%' => $paymentMethod->getTranslation('name')], locale: $locale),
        ];

        // @phpstan-ignore-next-line
        if ($paymentMethod->getTechnicalName() === 'swag_paypal_pui') {
            $values = $transaction->getTranslatedCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION)['deposit_bank_details'] ?? [];
            $ratePay = $this->translator->trans('paypal.payUponInvoice.document.paymentNoteRatepay', ['%companyName%' => $event->config->getCompanyName()], locale: $locale);

            $paymentMeans['information'] .= ' | ' . $ratePay;
            $paymentMeans['typeCode'] = self::UNTDID_4461_42;
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
