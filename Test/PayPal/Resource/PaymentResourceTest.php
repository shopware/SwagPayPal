<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Payment\PaymentBuilderService;
use SwagPayPal\PayPal\PaymentStatus;
use SwagPayPal\PayPal\Resource\PaymentResource;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\CreatePaymentResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use SwagPayPal\Test\Mock\Repositories\LanguageRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderRepoMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;

class PaymentResourceTest extends TestCase
{
    use PaymentTransactionTrait;

    public function testCreate(): void
    {
        $paymentResource = $this->createPaymentResource();

        $context = Context::createDefaultContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();

        $payment = $this->createPaymentBuilderService()->getPayment($paymentTransaction, $context);

        $createdPayment = $paymentResource->create($payment, $context);

        self::assertInstanceOf(Payment::class, $createdPayment);
        self::assertSame(CreatePaymentResponseFixture::CREATE_PAYMENT_ID, $createdPayment->getId());
        $transaction = $createdPayment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        $link = $createdPayment->getLinks()[1];
        self::assertInstanceOf(Payment\Link::class, $link);
        self::assertSame(CreatePaymentResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $link->getHref());
    }

    public function testExecute(): void
    {
        $paymentResource = $this->createPaymentResource();

        $context = Context::createDefaultContext();

        $executedPayment = $paymentResource->execute('testPayerId', 'testPaymentId', $context);

        self::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        self::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);
        self::assertSame(PaymentStatus::PAYMENT_COMPLETED, $transaction->getRelatedResources()[0]->getSale()->getState());
    }

    private function createPaymentResource(): PaymentResource
    {
        return new PaymentResource(
            new PayPalClientFactoryMock(
                new TokenResourceMock(
                    new CacheMock(),
                    new TokenClientFactoryMock()
                ),
                new SettingsProviderMock()
            )
        );
    }

    private function createPaymentBuilderService(): PaymentBuilderService
    {
        $paymentBuilderService = new PaymentBuilderService(
            new LanguageRepoMock(),
            new SalesChannelRepoMock(),
            new OrderRepoMock(),
            new SettingsProviderMock()
        );

        return $paymentBuilderService;
    }
}
