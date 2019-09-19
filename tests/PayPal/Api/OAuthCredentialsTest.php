<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Api;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\PayPal\Api\OAuthCredentials;

class OAuthCredentialsTest extends TestCase
{
    public function testToString(): void
    {
        $credentials = new OAuthCredentials();
        $restId = 'testRestId';
        $restSecret = 'testRestSecret';
        $credentials->setRestId($restId);
        $credentials->setRestSecret($restSecret);

        static::assertSame('Basic dGVzdFJlc3RJZDp0ZXN0UmVzdFNlY3JldA==', (string) $credentials);
    }
}
