<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Symfony\Component\HttpFoundation\Response;

class WebhookAlreadyExistsExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $webhookUrl = 'www.test.de';
        $exception = new WebhookAlreadyExistsException($webhookUrl);

        static::assertSame(sprintf('WebhookUrl "%s" already exists', $webhookUrl), $exception->getMessage());
        static::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
    }
}
