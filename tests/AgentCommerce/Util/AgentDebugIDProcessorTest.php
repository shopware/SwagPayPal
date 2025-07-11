<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Tests\AgentCommerce\Util;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\AbstractRouteScope;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgentCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\Util\AgentDebugIDProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentDebugIDProcessor::class)]
class AgentDebugIDProcessorTest extends TestCase
{
    public function testInvokeWithoutRequest(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);

        $processor = self::createProcessor($request, [new AgentRouteScope()]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame([], $record->extra);
    }

    public function testInvokeWithNonAgentScope(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['invalid_scope']);

        $invalidScope = new class extends AbstractRouteScope {
            public function getId(): string
            {
                return 'invalid_scope';
            }

            public function isAllowed(Request $request): bool
            {
                return true;
            }
        };

        $processor = self::createProcessor($request, [$invalidScope]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame([], $record->extra);
    }

    public function testInvokeWithoutContextObject(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);

        $processor = self::createProcessor($request, [new AgentRouteScope()]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame([], $record->extra);
    }

    public function testInvokeWithNonContextObject(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, new \stdClass());

        $processor = self::createProcessor($request, [new AgentRouteScope()]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame([], $record->extra);
    }

    public function testInvokeWithNonAgentSource(): void
    {
        $context = Context::createDefaultContext(new SystemSource());

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);

        $processor = self::createProcessor($request, [new AgentRouteScope()]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame([], $record->extra);
    }

    public function testInvokeWithoutDebugId(): void
    {
        $source = new AgentSource('test-agent', new \DateTimeImmutable(), new \DateTimeImmutable(), ['test']);

        $context = Context::createDefaultContext($source);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);

        $processor = self::createProcessor($request, [new AgentRouteScope()]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame([], $record->extra);
    }

    public function testInvoke(): void
    {
        $source = new AgentSource('test-agent', new \DateTimeImmutable(), new \DateTimeImmutable(), ['test'], 'debug-id-123');

        $context = Context::createDefaultContext($source);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);

        $processor = self::createProcessor($request, [new AgentRouteScope()]);
        $record = self::createLogRecord();

        $record = $processor($record);

        static::assertSame(['debugId' => 'debug-id-123'], $record->extra);
    }

    /**
     * @param list<AbstractRouteScope> $scopes
     */
    private static function createProcessor(?Request $request = null, array $scopes = []): AgentDebugIDProcessor
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        $routeScopeRegistry = new RouteScopeRegistry($scopes);

        $processor = new AgentDebugIDProcessor();
        $processor->setRequestStack($requestStack);
        $processor->setRouteScopeRegistry($routeScopeRegistry);

        return $processor;
    }

    private static function createLogRecord(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'test', Level::Debug, 'Test message', [], []);
    }
}
