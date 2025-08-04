<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentSource::class)]
class AgentSourceTest extends TestCase
{
    public function testConstruct(): void
    {
        $merchantId = 'test-merchant-id';
        $issuedAt = new \DateTimeImmutable();
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $scope = [AgentSource::SCOPE_CART, AgentSource::SCOPE_CHECKOUT];
        $salesChannelId = 'sales-channel-id';
        $debugId = 'test-debug-id';

        $source = new AgentSource($merchantId, $issuedAt, $expiresAt, $scope, $salesChannelId, $debugId);

        static::assertSame($merchantId, $source->merchantId);
        static::assertSame($issuedAt, $source->issuedAt);
        static::assertSame($expiresAt, $source->expiresAt);
        static::assertSame($scope, $source->scope);
        static::assertSame($salesChannelId, $source->salesChannelId);
        static::assertSame($debugId, $source->debugId);

        static::assertSame(AgentRouteScope::ID, $source->type);

        static::assertTrue($source->hasScope(AgentSource::SCOPE_CART));
        static::assertTrue($source->hasScope(AgentSource::SCOPE_CHECKOUT));
        static::assertFalse($source->hasScope('non-existent-scope'));

        static::assertFalse($source->isExpired());
    }

    public function testExpiredSource(): void
    {
        $merchantId = 'test-merchant-id';
        $issuedAt = new \DateTimeImmutable('-2 hours');
        $expiresAt = new \DateTimeImmutable('-1 hour');
        $scope = [AgentSource::SCOPE_CART];
        $debugId = 'test-debug-id';

        $source = new AgentSource($merchantId, $issuedAt, $expiresAt, $scope, $debugId);

        static::assertTrue($source->isExpired());
    }
}
