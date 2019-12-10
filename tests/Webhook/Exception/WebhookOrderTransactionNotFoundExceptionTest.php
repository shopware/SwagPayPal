<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
