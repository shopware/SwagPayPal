<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Symfony\Component\HttpFoundation\Response;

class WebhookIdInvalidExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $webhookId = 'testWebhookId';
        $exception = new WebhookIdInvalidException($webhookId);

        static::assertSame(sprintf('Webhook with ID "%s" is invalid', $webhookId), $exception->getMessage());
        static::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
    }
}
