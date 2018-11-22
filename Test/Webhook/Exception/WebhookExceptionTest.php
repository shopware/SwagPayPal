<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use SwagPayPal\Webhook\Exception\WebhookException;
use Symfony\Component\HttpFoundation\Response;

class WebhookExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $webhookType = 'testType';
        $message = 'testMessage';
        $exception = new WebhookException($webhookType, $message);

        self::assertSame($message, $exception->getMessage());
        self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
    }

    public function testGetEventType(): void
    {
        $webhookType = 'testType';
        $message = 'testMessage';
        $exception = new WebhookException($webhookType, $message);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($webhookType, $exception->getEventType());
    }
}
