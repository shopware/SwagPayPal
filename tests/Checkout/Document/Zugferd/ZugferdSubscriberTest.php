<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Document\Zugferd;

use horstoeko\zugferd\ZugferdDocumentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\Zugferd\ZugferdDocument;
use Shopware\Core\Checkout\Document\Zugferd\ZugferdInvoiceGeneratedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Document\Zugferd\ZugferdSubscriber;
use Swag\PayPal\SwagPayPal;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ZugferdSubscriberTest extends TestCase
{
    #[DataProvider('dataProviderTransaction')]
    public function testTest(?OrderTransactionEntity $transaction, array $expected): void
    {
        // return last snippet path
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $message): string => substr($message, strrpos($message, '.') + 1));

        $builderMock = $this->createMock(ZugferdDocumentBuilder::class);
        $builderMock
            ->expects(empty($expected) ? $this->never() : $this->once())
            ->method('addDocumentPaymentMean')
            ->willReturnCallback(static function (...$arguments) use ($expected, $builderMock): ZugferdDocumentBuilder {
                foreach ($expected as $value) {
                    static::assertContains($value, $arguments);
                }

                return $builderMock;
            });

        $order = new OrderEntity();
        if ($transaction !== null) {
            /** @phpstan-ignore function.alreadyNarrowedType */
            if (\method_exists($order, 'setPrimaryOrderTransaction')) {
                $order->setPrimaryOrderTransaction($transaction);
            }
            $order->setTransactions(new OrderTransactionCollection([$transaction]));
        }

        $event = new ZugferdInvoiceGeneratedEvent(
            new ZugferdDocument($builderMock),
            $order,
            new DocumentConfiguration(),
            Context::createDefaultContext()
        );

        (new ZugferdSubscriber($translator))->generateInvoice($event);
    }

    public static function dataProviderTransaction(): array
    {
        return [
            'no transaction' => [null, []],
            'no payment method' => [self::createTransaction(), []],
            'default' => [self::createTransaction('test'),
                [
                    'typeCode' => 'ZZZ',
                    'information' => 'paymentMethod | orderId',
                ],
            ],
            'pui' => [self::createTransaction('pui'),
                [
                    'typeCode' => '42',
                    'information' => 'paymentMethod | paymentNoteRatepay',
                    'payeeIban' => 'SomeIban',
                    'payeeAccountName' => 'SomeAccountHolderName',
                    'payeeBic' => 'SomeBic',
                ],
            ],
        ];
    }

    private static function createTransaction(string $paymentMethod = ''): OrderTransactionEntity
    {
        $bankDetails = ['deposit_bank_details' => [
            'iban' => 'SomeIban',
            'account_holder_name' => 'SomeAccountHolderName',
            'bic' => 'SomeBic',
        ]];

        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());
        $transaction->setTranslated(['customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PUI_INSTRUCTION => $bankDetails]]);

        if ($paymentMethod !== '') {
            $transaction->setPaymentMethod(self::createPaymentMethod($paymentMethod));
        }

        return $transaction;
    }

    private static function createPaymentMethod(string $technicalName): PaymentMethodEntity
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTechnicalName('swag_paypal_' . $technicalName);
        $paymentMethod->setTranslated(['name' => 'Swag Paypal Payment']);

        return $paymentMethod;
    }
}
