<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\Controller\PayPalPaymentController;
use SwagPayPal\PayPal\Exception\RequiredParameterMissingException;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\GetPaymentSaleResponseFixture;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentControllerTest extends TestCase
{
    use ServicesTrait;

    public function testGetPaymentDetails(): void
    {
        $controller = $this->createPaymentController();

        $request = new Request([PayPalPaymentController::REQUEST_PARAMETER_PAYMENT_ID => 'testPaymentId']);
        $context = Context::createDefaultContext();
        $response = $controller->getPaymentDetails($request, $context);

        $paymentDetails = json_decode($response->getContent(), true);

        self::assertSame(
            GetPaymentSaleResponseFixture::TRANSACTION_AMOUNT_DETAILS_SUBTOTAL,
            $paymentDetails['transactions'][0]['amount']['details']['subtotal']
        );
    }

    public function testGetPaymentDetailsThrowsException(): void
    {
        $controller = $this->createPaymentController();

        $request = new Request();
        $context = Context::createDefaultContext();

        $this->expectException(RequiredParameterMissingException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Required parameter "%s" is missing or invalid',
                PayPalPaymentController::REQUEST_PARAMETER_PAYMENT_ID
            )
        );
        $controller->getPaymentDetails($request, $context);
    }

    private function createPaymentController(): PayPalPaymentController
    {
        return new PayPalPaymentController($this->createPaymentResource());
    }
}
