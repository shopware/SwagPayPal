<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Payment\PayPalPaymentMethodController;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Util\PaymentMethodUtilTest;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PayPalPaymentMethodControllerTest extends TestCase
{
    use KernelTestBehaviour;
    use DatabaseTransactionBehaviour;

    public function testSetPayPalPaymentMethodAsSalesChannelDefault(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $paymentMethodUtil = new PaymentMethodUtil(new PaymentMethodRepoMock(), $salesChannelRepoMock);
        $context = Context::createDefaultContext();

        $response = (new PayPalPaymentMethodController($paymentMethodUtil))
            ->setPayPalPaymentMethodAsSalesChannelDefault(new Request(), $context);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $updates = $salesChannelRepoMock->getUpdateData();
        static::assertCount(1, $updates);
        $updateData = $updates[0];
        static::assertArrayHasKey('id', $updateData);
        static::assertSame(PaymentMethodUtilTest::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD, $updateData['id']);
        static::assertArrayHasKey('paymentMethodId', $updateData);
        $payPalPaymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId($context);
        static::assertNotNull($payPalPaymentMethodId);
        static::assertSame($payPalPaymentMethodId, $updateData['paymentMethodId']);
    }
}
