<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Plus;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\PayPal\Checkout\Plus\PlusPaymentFinalizeController;
use Swag\PayPal\Test\Mock\Payment\AsyncPaymentHandlerMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlusPaymentFinalizeControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public const WITHOUT_TRANSACTION = 'noTransactionFound';
    public const WITHOUT_ORDER = 'noOrderFound';

    public function testFinalizeTransaction(): void
    {
        $response = $this->createController()->finalizeTransaction(
            new Request(),
            Generator::createSalesChannelContext()
        );

        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame('/checkout/finish?orderId=testOrderId', $response->getTargetUrl());
    }

    public function testFinalizeTransactionWithoutTransaction(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getContext()->addExtension(self::WITHOUT_TRANSACTION, new Entity());
        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->createController()->finalizeTransaction(new Request(), $salesChannelContext);
    }

    public function testFinalizeTransactionWithoutOrder(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $salesChannelContext->getContext()->addExtension(self::WITHOUT_ORDER, new Entity());
        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->createController()->finalizeTransaction(new Request(), $salesChannelContext);
    }

    public function testFinalizeTransactionCustomerCancel(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $this->expectException(CustomerCanceledAsyncPaymentException::class);
        $this->expectExceptionMessage('The customer canceled the external payment process. Additional information:
Customer canceled the payment on the PayPal page');
        $request = new Request(['cancel' => true]);
        $this->createController()->finalizeTransaction($request, $salesChannelContext);
    }

    private function createController(): PlusPaymentFinalizeController
    {
        $controller = new PlusPaymentFinalizeController(new OrderTransactionRepoMock(), new AsyncPaymentHandlerMock());
        $controller->setContainer($this->getContainer());

        return $controller;
    }
}
