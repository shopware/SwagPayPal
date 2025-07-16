<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\SalesChannel\Response;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentCartResponse::class)]
class AgentCartResponseTest extends TestCase
{
    public function testConstruct(): void
    {
        $response = new AgentCartResponse('test-token');

        static::assertSame(['id' => 'test-token'], $response->getObject()->all());
    }
}
