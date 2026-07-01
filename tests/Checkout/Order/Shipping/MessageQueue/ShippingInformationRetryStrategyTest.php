<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Order\Shipping\MessageQueue;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ApiException;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessage;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationRetryStrategy;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingInformationRetryStrategy::class)]
class ShippingInformationRetryStrategyTest extends TestCase
{
    private const NOW = '2026-01-01T00:00:00+00:00';

    public function testIsRetryableDelegatesToDecoratedStrategy(): void
    {
        $envelope = new Envelope(new ShippingInformationMessage('order-delivery-id'));
        $throwable = new \RuntimeException('Failed');
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('isRetryable')
            ->with($envelope, $throwable)
            ->willReturn(false);

        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());

        static::assertFalse($strategy->isRetryable($envelope, $throwable));
    }

    public function testUsesRetryAfterDelayForRateLimitedShippingMessage(): void
    {
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->willReturn(1000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());

        $delay = $strategy->getWaitingTime(
            new Envelope(new ShippingInformationMessage('order-delivery-id')),
            new PayPalApiException(
                ApiException::CODE_RATE_LIMIT_REACHED,
                'Rate limit reached',
                429,
                retryAt: self::createRetryAt(120),
            ),
        );

        static::assertGreaterThanOrEqual(110000, $delay);
        static::assertLessThanOrEqual(120000, $delay);
    }

    public function testUsesRetryAfterDelayForShippingMessageWhenErrorCodeDiffers(): void
    {
        $payPalException = self::createRateLimitException(120, 'OTHER_RATE_LIMIT');
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->willReturn(1000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClockForRetryAt($payPalException));

        $delay = $strategy->getWaitingTime(
            new Envelope(new ShippingInformationMessage('order-delivery-id')),
            $payPalException,
        );

        static::assertGreaterThanOrEqual(110000, $delay);
        static::assertLessThanOrEqual(120000, $delay);
    }

    public function testUsesRetryAfterDelayForWrappedRateLimitedShippingMessage(): void
    {
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->willReturn(1000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());
        $envelope = new Envelope(new ShippingInformationMessage('order-delivery-id'));
        $payPalException = new PayPalApiException(
            ApiException::CODE_RATE_LIMIT_REACHED,
            'Rate limit reached',
            429,
            retryAt: self::createRetryAt(120),
        );

        $delay = $strategy->getWaitingTime(
            $envelope,
            new HandlerFailedException($envelope, [$payPalException]),
        );

        static::assertGreaterThanOrEqual(110000, $delay);
        static::assertLessThanOrEqual(120000, $delay);
    }

    public function testKeepsDecoratedWaitingTimeWhenItIsGreaterThanRetryAfterDelay(): void
    {
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->willReturn(180000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());

        $delay = $strategy->getWaitingTime(
            new Envelope(new ShippingInformationMessage('order-delivery-id')),
            new PayPalApiException(
                ApiException::CODE_RATE_LIMIT_REACHED,
                'Rate limit reached',
                429,
                retryAt: self::createRetryAt(120),
            ),
        );

        static::assertSame(180000, $delay);
    }

    public function testMessengerRetryUsesRetryAfterDelay(): void
    {
        $payPalException = self::createRateLimitException(120);
        $envelope = new Envelope(new ShippingInformationMessage('order-delivery-id'));
        $event = new WorkerMessageFailedEvent(
            $envelope,
            'async',
            new HandlerFailedException($envelope, [$payPalException]),
        );
        $sentEnvelope = null;

        $sender = $this->createMock(SenderInterface::class);
        $sender
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (Envelope $envelope) use (&$sentEnvelope): Envelope {
                $sentEnvelope = $envelope;

                return $envelope;
            });

        $listener = $this->createRetryListener(
            $sender,
            new ShippingInformationRetryStrategy(new MultiplierRetryStrategy(3, 1000, 2, 0, 0), self::createClockForRetryAt($payPalException)),
        );
        $listener->onMessageFailed($event);

        static::assertTrue($event->willRetry());
        static::assertInstanceOf(Envelope::class, $sentEnvelope);
        $delayStamp = $sentEnvelope->last(DelayStamp::class);
        $redeliveryStamp = $sentEnvelope->last(RedeliveryStamp::class);
        static::assertInstanceOf(DelayStamp::class, $delayStamp);
        static::assertInstanceOf(RedeliveryStamp::class, $redeliveryStamp);
        static::assertGreaterThanOrEqual(110000, $delayStamp->getDelay());
        static::assertLessThanOrEqual(120000, $delayStamp->getDelay());
        static::assertSame(1, $redeliveryStamp->getRetryCount());
    }

    public function testMessengerRetryStopsWhenMaxRetriesAreReachedEvenWithRetryAfter(): void
    {
        $payPalException = self::createRateLimitException(120);
        $envelope = (new Envelope(new ShippingInformationMessage('order-delivery-id')))
            ->with(new RedeliveryStamp(3));
        $event = new WorkerMessageFailedEvent(
            $envelope,
            'async',
            new HandlerFailedException($envelope, [$payPalException]),
        );

        $sender = $this->createMock(SenderInterface::class);
        $sender
            ->expects($this->never())
            ->method('send');

        $listener = $this->createRetryListener(
            $sender,
            new ShippingInformationRetryStrategy(new MultiplierRetryStrategy(3, 1000, 2, 0, 0), self::createClockForRetryAt($payPalException)),
        );
        $listener->onMessageFailed($event);

        static::assertFalse($event->willRetry());
    }

    public function testFallsBackToDecoratedStrategyForOtherMessages(): void
    {
        $envelope = new Envelope(new \stdClass());
        $payPalException = new PayPalApiException(
            ApiException::CODE_RATE_LIMIT_REACHED,
            'Rate limit reached',
            429,
            retryAt: self::createRetryAt(120),
        );
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->with($envelope, $payPalException)
            ->willReturn(1000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());

        $delay = $strategy->getWaitingTime($envelope, $payPalException);

        static::assertSame(1000, $delay);
    }

    public function testFallsBackToDecoratedStrategyWithoutRetryAt(): void
    {
        $envelope = new Envelope(new ShippingInformationMessage('order-delivery-id'));
        $payPalException = new PayPalApiException(
            ApiException::CODE_RATE_LIMIT_REACHED,
            'Rate limit reached',
            429,
        );
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->with($envelope, $payPalException)
            ->willReturn(1000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());

        $delay = $strategy->getWaitingTime($envelope, $payPalException);

        static::assertSame(1000, $delay);
    }

    public function testFallsBackToDecoratedStrategyForOtherPayPalErrors(): void
    {
        $envelope = new Envelope(new ShippingInformationMessage('order-delivery-id'));
        $payPalException = new PayPalApiException(
            PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND,
            'Not found',
            404,
        );
        $decorated = $this->createMock(RetryStrategyInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getWaitingTime')
            ->with($envelope, $payPalException)
            ->willReturn(1000);
        $strategy = new ShippingInformationRetryStrategy($decorated, self::createClock());

        $delay = $strategy->getWaitingTime($envelope, $payPalException);

        static::assertSame(1000, $delay);
    }

    private static function createRateLimitException(int $retryAfterSeconds, string $name = ApiException::CODE_RATE_LIMIT_REACHED): PayPalApiException
    {
        return new PayPalApiException(
            $name,
            'Rate limit reached',
            429,
            retryAt: self::createRetryAt($retryAfterSeconds),
        );
    }

    private static function createClock(?\DateTimeImmutable $now = null): MockClock
    {
        return new MockClock($now ?? self::NOW);
    }

    private static function createRetryAt(int $seconds): \DateTimeImmutable
    {
        return self::createClock()->now()->modify(\sprintf('+%d seconds', $seconds));
    }

    private static function createClockForRetryAt(PayPalApiException $exception): MockClock
    {
        $retryAt = $exception->getRetryAt();
        static::assertInstanceOf(\DateTimeImmutable::class, $retryAt);

        return self::createClock($retryAt->modify('-120 seconds'));
    }

    private function createRetryListener(
        SenderInterface $sender,
        RetryStrategyInterface $retryStrategy,
    ): SendFailedMessageForRetryListener {
        $sendersLocator = $this->createMock(ContainerInterface::class);
        $sendersLocator
            ->method('has')
            ->with('async')
            ->willReturn(true);
        $sendersLocator
            ->method('get')
            ->with('async')
            ->willReturn($sender);

        $retryStrategyLocator = $this->createMock(ContainerInterface::class);
        $retryStrategyLocator
            ->method('has')
            ->with('async')
            ->willReturn(true);
        $retryStrategyLocator
            ->method('get')
            ->with('async')
            ->willReturn($retryStrategy);

        return new SendFailedMessageForRetryListener($sendersLocator, $retryStrategyLocator);
    }
}
