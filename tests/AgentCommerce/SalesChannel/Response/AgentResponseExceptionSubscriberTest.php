<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\SalesChannel\Response;

use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RouteScopeRegistry;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Routing\AgentRouteScope;
use Swag\PayPal\AgentCommerce\Routing\AgentSource;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentResponseExceptionSubscriber;
use Swag\PayPal\AgentCommerce\Struct\V1\AgentError;
use Swag\PayPal\AgentCommerce\Struct\V1\AgentErrorDetail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AgentResponseExceptionSubscriber::class)]
class AgentResponseExceptionSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertSame(
            [
                KernelEvents::EXCEPTION => [
                    ['onKernelException', 0],
                ],
            ],
            AgentResponseExceptionSubscriber::getSubscribedEvents()
        );
    }

    public function testOnKernelExceptionWithoutContext(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $event = $this->createEvent($request, new \Exception('Test exception'));

        $subscriber = new AgentResponseExceptionSubscriber(new Logger('test'), new RouteScopeRegistry([new AgentRouteScope()]));
        $subscriber->onKernelException($event);

        static::assertNotNull($event->getResponse());

        $response = $event->getResponse();
        static::assertNotFalse($response->getContent());

        $content = \json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $error = (new AgentError())->assign($content);

        static::assertNull($error->getDebugId());
    }

    public function testOnKernelExceptionWithNonContextObject(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, new \stdClass());
        $event = $this->createEvent($request, new \Exception('Test exception'));

        $subscriber = new AgentResponseExceptionSubscriber(new Logger('test'), new RouteScopeRegistry([new AgentRouteScope()]));
        $subscriber->onKernelException($event);

        static::assertNotNull($event->getResponse());

        $response = $event->getResponse();
        static::assertNotFalse($response->getContent());

        $content = \json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $error = (new AgentError())->assign($content);

        static::assertNull($error->getDebugId());
    }

    public function testOnKernelExceptionWithNonPayPalAgentSource(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext(new SystemSource());
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
        $event = $this->createEvent($request, new \Exception('Test exception'));

        $subscriber = new AgentResponseExceptionSubscriber(new Logger('test'), new RouteScopeRegistry([new AgentRouteScope()]));
        $subscriber->onKernelException($event);

        static::assertNull($event->getResponse());
    }

    public function testOnKernelExceptionPayPalAgentException(): void
    {
        $source = new AgentSource('MERCHANT_ID', new \DateTimeImmutable(), new \DateTimeImmutable('+1 hour'), ['cart'], 'sales-channel-id', 'debug-id');

        $request = new Request();
        $context = Context::createDefaultContext($source);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
        $event = $this->createEvent($request, AgentException::requiredFieldsMissing('foo', 'bar'));

        $subscriber = new AgentResponseExceptionSubscriber(new Logger('test'), new RouteScopeRegistry([new AgentRouteScope()]));
        $subscriber->onKernelException($event);

        static::assertNotNull($event->getResponse());

        $response = $event->getResponse();
        static::assertNotFalse($response->getContent());

        $content = \json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $error = (new AgentError())->assign($content);

        static::assertSame('INVALID_REQUEST', $error->getName());
        static::assertSame('Required field \'foo, bar\' is missing', $error->getMessage());
        static::assertSame(400, $error->getCode());
        static::assertSame('debug-id', $error->getDebugId());

        $details = $error->getDetails();

        static::assertNotNull($details);
        static::assertContainsOnlyInstancesOf(AgentErrorDetail::class, $details);
        static::assertCount(2, $details);

        static::assertSame('foo', $details->first()?->getField());
        static::assertSame('MISSING_REQUIRED_FIELD', $details->first()->getIssue());
        static::assertSame('The field \'foo\' is required and cannot be empty', $details->first()->getDescription());

        static::assertSame('bar', $details->last()?->getField());
        static::assertSame('MISSING_REQUIRED_FIELD', $details->last()->getIssue());
        static::assertSame('The field \'bar\' is required and cannot be empty', $details->last()->getDescription());
    }

    public function testOnKernelExceptionHttpException(): void
    {
        $source = new AgentSource('MERCHANT_ID', new \DateTimeImmutable(), new \DateTimeImmutable('+1 hour'), ['cart'], 'sales-channel-id', 'debug-id');

        $request = new Request();
        $context = Context::createDefaultContext($source);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
        $event = $this->createEvent($request, PaymentException::asyncProcessInterrupted(Uuid::randomHex(), 'Error message'));

        $subscriber = new AgentResponseExceptionSubscriber(new Logger('test'), new RouteScopeRegistry([new AgentRouteScope()]));
        $subscriber->onKernelException($event);

        static::assertNotNull($event->getResponse());

        $response = $event->getResponse();
        static::assertNotFalse($response->getContent());

        $content = \json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $error = (new AgentError())->assign($content);

        static::assertSame('CHECKOUT__ASYNC_PAYMENT_PROCESS_INTERRUPTED', $error->getName());
        static::assertSame(400, $error->getCode());
        static::assertSame('debug-id', $error->getDebugId());
    }

    public function testOnKernelExceptionGenericThrowable(): void
    {
        $source = new AgentSource('MERCHANT_ID', new \DateTimeImmutable(), new \DateTimeImmutable('+1 hour'), ['cart'], 'sales-channel-id', 'debug-id');

        $request = new Request();
        $context = Context::createDefaultContext($source);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, [AgentRouteScope::ID]);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, $context);
        $event = $this->createEvent($request, new \Exception('Generic error'));

        $subscriber = new AgentResponseExceptionSubscriber(new Logger('test'), new RouteScopeRegistry([new AgentRouteScope()]));
        $subscriber->onKernelException($event);

        static::assertNotNull($event->getResponse());

        $response = $event->getResponse();
        static::assertNotFalse($response->getContent());

        $content = \json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $error = (new AgentError())->assign($content);

        static::assertSame('UNKNOWN_ERROR', $error->getName());
        static::assertSame('Generic error', $error->getMessage());
        static::assertSame(500, $error->getCode());
        static::assertSame('debug-id', $error->getDebugId());
    }

    private function createEvent(Request $request, \Throwable $e): ExceptionEvent
    {
        return new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $e);
    }
}
