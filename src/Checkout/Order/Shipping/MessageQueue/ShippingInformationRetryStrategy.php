<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Order\Shipping\MessageQueue;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ShippingInformationRetryStrategy implements RetryStrategyInterface
{
    public function __construct(
        private readonly RetryStrategyInterface $decorated
    ) {
    }

    public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
    {
        return $this->decorated->isRetryable($message, $throwable);
    }

    public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
    {
        $waitingTime = $this->decorated->getWaitingTime($message, $throwable);
        if (!$message->getMessage() instanceof ShippingInformationMessage) {
            return $waitingTime;
        }

        $payPalException = $this->extractPayPalException($throwable);
        $retryDelay = $payPalException?->getRetryDelay();
        if ($retryDelay !== null) {
            return max($retryDelay, $waitingTime);
        }

        return $waitingTime;
    }

    private function extractPayPalException(?\Throwable $throwable): ?PayPalApiException
    {
        if ($throwable instanceof PayPalApiException) {
            return $throwable;
        }

        if ($throwable instanceof HandlerFailedException) {
            foreach ($throwable->getWrappedExceptions(PayPalApiException::class, true) as $wrappedException) {
                return $wrappedException;
            }
        }

        return null;
    }
}
