<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgenticCommerce\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\AgenticCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentRouteScope::class)]
class AgentRouteScopeTest extends TestCase
{
    public function testIsAllowedWithMissingAuthorizationHeader(): void
    {
        $scope = new AgentRouteScope();

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        static::assertFalse($scope->isAllowed($request));
    }

    public function testIsAllowedWithMissingContentTypeHeader(): void
    {
        $scope = new AgentRouteScope();

        $request = new Request();
        $request->headers->set('Authorization', 'ey.jwt.token');

        static::assertFalse($scope->isAllowed($request));
    }

    public function testIsAllowedWithWrongContentType(): void
    {
        $scope = new AgentRouteScope();

        $request = new Request();
        $request->headers->set('Authorization', 'ey.jwt.token');
        $request->headers->set('Content-Type', 'text/plain');

        static::assertFalse($scope->isAllowed($request));
    }

    public function testIsAllowedWithNoContext(): void
    {
        $scope = new AgentRouteScope();

        $request = new Request();
        $request->headers->set('Authorization', 'ey.jwt.token');
        $request->headers->set('Content-Type', 'application/json');

        static::assertFalse($scope->isAllowed($request));
    }

    public function testIsAllowedWithWrongContextObject(): void
    {
        $scope = new AgentRouteScope();

        $request = new Request();
        $request->headers->set('Authorization', 'ey.jwt.token');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, new \stdClass());

        static::assertFalse($scope->isAllowed($request));
    }

    public function testIsAllowedWithWrongContextSource(): void
    {
        $scope = new AgentRouteScope();

        $context = Context::createDefaultContext(new SystemSource());

        $request = new Request();
        $request->headers->set('Authorization', 'ey.jwt.token');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);

        static::assertFalse($scope->isAllowed($request));
    }

    public function testIsAllowed(): void
    {
        $scope = new AgentRouteScope();
        $source = new AgentSource('MERCHANT_ID', new \DateTimeImmutable(), new \DateTimeImmutable('+1 hour'), ['cart'], 'sales-channel-id', 'debug-id');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->expects($this->once())
            ->method('getContext')
            ->willReturn(Context::createDefaultContext($source));

        $request = new Request();
        $request->headers->set('Authorization', 'ey.jwt.token');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $salesChannelContext);

        static::assertTrue($scope->isAllowed($request));
    }

    public function testGetId(): void
    {
        static::assertSame('paypal-agent', (new AgentRouteScope())->getId());
    }
}
