<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Symfony\Component\HttpFoundation\Response;

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
