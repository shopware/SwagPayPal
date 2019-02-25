<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook\Exception;

use PHPUnit\Framework\TestCase;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;
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
