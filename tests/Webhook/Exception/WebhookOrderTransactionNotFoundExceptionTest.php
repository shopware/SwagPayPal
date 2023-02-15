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

/**
 * @internal
 */
class WebhookOrderTransactionNotFoundExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $webhookType = 'testType';
        $reason = 'with the PayPal ID "testPayPalTransactionId"';
        $exception = new WebhookOrderTransactionNotFoundException($reason, $webhookType);

        static::assertSame(
            \sprintf(
                '[PayPal %s Webhook] Could not find associated order transaction with the PayPal ID "%s"',
                $webhookType,
                'testPayPalTransactionId'
            ),
            $exception->getMessage()
        );
        static::assertSame(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }
}
