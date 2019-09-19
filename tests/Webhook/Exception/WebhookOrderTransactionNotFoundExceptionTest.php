<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class WebhookOrderTransactionNotFoundExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $webhookType = 'testType';
        $payPalTransactionId = 'testPayPalTransactionId';
        $exception = new WebhookOrderTransactionNotFoundException($payPalTransactionId, $webhookType);

        static::assertSame(
            sprintf(
                '[PayPal %s Webhook] Could not find associated order with the PayPal ID "%s"',
                $webhookType,
                $payPalTransactionId
            ),
            $exception->getMessage()
        );
        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
