<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\API;

use PHPUnit\Framework\TestCase;
use SwagPayPal\PayPal\Api\OAuthCredentials;

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
