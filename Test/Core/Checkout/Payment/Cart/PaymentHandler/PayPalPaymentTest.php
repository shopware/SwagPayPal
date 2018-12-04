<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Core\Checkout\Payment\Cart\PaymentHandler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagPayPal\Core\Checkout\Payment\Cart\PaymentHandler\PayPalPayment;
use SwagPayPal\PayPal\Payment\PaymentBuilderService;
use SwagPayPal\PayPal\Resource\PaymentResource;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\CreatePaymentResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use SwagPayPal\Test\Mock\Repositories\LanguageRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentTest extends TestCase
{
    use PaymentTransactionTrait;

    public const PAYER_ID_PAYMENT_INCOMPLETE = 'testPayerIdIncomplete';

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    public function setUp(): void
    {
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
    }

    public function testPay(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $response = $handler->pay($paymentTransaction, $context);

        self::assertNotNull($response);
        if ($response === null) {
            return;
        }

        self::assertSame(CreatePaymentResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $response->getTargetUrl());

        $updatedData = $this->orderTransactionRepo->getData();
        self::assertSame(
            CreatePaymentResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['details'][PayPalPayment::TRANSACTION_DETAILS_JSON_KEY]['transactionId']
        );
    }

    public function testFinalize(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame(Defaults::ORDER_TRANSACTION_COMPLETED, $updatedData['orderTransactionStateId']);
    }

    public function testFinalizeWithCancel(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = new Request(['cancel' => true]);
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame(Defaults::ORDER_TRANSACTION_FAILED, $updatedData['orderTransactionStateId']);
    }

    public function testFinalizePaymentNotCompleted(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest(true);
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame(Defaults::ORDER_TRANSACTION_OPEN, $updatedData['orderTransactionStateId']);
    }

    private function createPayPalPaymentHandler(): PayPalPayment
    {
        $settingsProvider = new SettingsProviderMock();

        return new PayPalPayment(
            $this->orderTransactionRepo,
            new PaymentResource(
                new PayPalClientFactoryMock(
                    new TokenResourceMock(
                        new CacheMock(),
                        new TokenClientFactoryMock()
                    ),
                    $settingsProvider
                )
            ),
            new PaymentBuilderService(
                new LanguageRepoMock(),
                new SalesChannelRepoMock(),
                new OrderRepoMock(),
                $settingsProvider
            )
        );
    }

    private function createRequest(bool $payerIdIncompletePayment = false): Request
    {
        $payerId = $payerIdIncompletePayment ? self::PAYER_ID_PAYMENT_INCOMPLETE : 'testPayerId';
        $paymentId = 'testPaymentId';
        $request = new Request([
            'PayerID' => $payerId,
            'paymentId' => $paymentId,
        ]);

        return $request;
    }
}
