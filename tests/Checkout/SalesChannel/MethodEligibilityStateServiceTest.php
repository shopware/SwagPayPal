<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityRoute;
use Swag\PayPal\Checkout\SalesChannel\MethodEligibilityStateService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(MethodEligibilityStateService::class)]
class MethodEligibilityStateServiceTest extends TestCase
{
    private SalesChannelContextPersister&MockObject $contextPersister;

    private MethodEligibilityStateService $stateService;

    protected function setUp(): void
    {
        $this->contextPersister = $this->createMock(SalesChannelContextPersister::class);
        $this->stateService = new MethodEligibilityStateService($this->contextPersister);
    }

    #[DataProvider('unstartedSessionProvider')]
    public function testReadingContextStateDoesNotInitializeSession(bool $sessionAlreadyInstantiated): void
    {
        $request = new Request();
        $storage = new MockArraySessionStorage();
        $factoryCalls = 0;
        $request->setSessionFactory(static function () use ($storage, &$factoryCalls): Session {
            ++$factoryCalls;

            return new Session($storage);
        });

        if ($sessionAlreadyInstantiated) {
            $request->getSession();
        }
        $factoryCallsBeforeRead = $factoryCalls;

        $this->contextPersister
            ->expects($this->once())
            ->method('load')
            ->with('context-token', 'sales-channel-id')
            ->willReturn([MethodEligibilityRoute::SESSION_KEY => '["handler-from-context"]']);

        $handlers = $this->stateService->getIneligiblePaymentMethods($request, $this->createSalesChannelContext());

        static::assertSame(['handler-from-context'], $handlers);
        static::assertFalse($storage->isStarted());
        static::assertSame($factoryCallsBeforeRead, $factoryCalls);
    }

    public static function unstartedSessionProvider(): \Generator
    {
        yield 'request has only the lazy session factory' => [false];
        yield 'session was instantiated but not started' => [true];
    }

    #[DataProvider('unstartedSessionProvider')]
    public function testWritingContextStateDoesNotInitializeSession(bool $sessionAlreadyInstantiated): void
    {
        $request = new Request();
        $storage = new MockArraySessionStorage();
        $factoryCalls = 0;
        $request->setSessionFactory(static function () use ($storage, &$factoryCalls): Session {
            ++$factoryCalls;

            return new Session($storage);
        });

        if ($sessionAlreadyInstantiated) {
            $request->getSession();
        }
        $factoryCallsBeforeWrite = $factoryCalls;

        $this->contextPersister
            ->expects($this->once())
            ->method('save')
            ->with(
                'context-token',
                [MethodEligibilityRoute::SESSION_KEY => '[]'],
                'sales-channel-id',
                'customer-id',
            );

        $this->stateService->setIneligiblePaymentMethods($request, $this->createSalesChannelContext(), []);

        static::assertFalse($storage->isStarted());
        static::assertSame($factoryCallsBeforeWrite, $factoryCalls);
    }

    public function testReadingWithoutRequestNeverQueriesTheContextPersister(): void
    {
        $this->contextPersister->expects($this->never())->method('load');

        static::assertSame([], $this->stateService->getIneligiblePaymentMethods(null, $this->createSalesChannelContext()));
    }

    public function testStartedSessionWithoutHandlersNeverQueriesTheContextPersister(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $request->setSession($session);

        $this->contextPersister->expects($this->never())->method('load');

        static::assertSame([], $this->stateService->getIneligiblePaymentMethods($request, $this->createSalesChannelContext()));
    }

    public function testStartedSessionRemainsTheStorefrontStateStorage(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set(MethodEligibilityRoute::SESSION_KEY, ['handler-from-session']);
        $request->setSession($session);

        $this->contextPersister->expects($this->never())->method('load');
        $this->contextPersister->expects($this->never())->method('save');

        $context = $this->createSalesChannelContext();
        static::assertSame(
            ['handler-from-session'],
            $this->stateService->getIneligiblePaymentMethods($request, $context),
        );

        $this->stateService->setIneligiblePaymentMethods($request, $context, [\stdClass::class]);

        static::assertSame([\stdClass::class], $session->get(MethodEligibilityRoute::SESSION_KEY));
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $context = static::createStub(SalesChannelContext::class);
        $context->method('getToken')->willReturn('context-token');
        $context->method('getSalesChannelId')->willReturn('sales-channel-id');
        $context->method('getCustomerId')->willReturn('customer-id');

        return $context;
    }
}
