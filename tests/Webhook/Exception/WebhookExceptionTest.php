<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $webhookType = 'testType';
        $message = 'testMessage';
        $exception = new WebhookException($webhookType, $message);

        static::assertSame($message, $exception->getMessage());
        static::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
    }

    public function testGetEventType(): void
    {
        $webhookType = 'testType';
        $message = 'testMessage';
        $exception = new WebhookException($webhookType, $message);

        static::assertSame($message, $exception->getMessage());
        static::assertSame($webhookType, $exception->getEventType());
    }
}
