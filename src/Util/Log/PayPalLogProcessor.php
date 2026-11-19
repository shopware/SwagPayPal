<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\PlatformRequest;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Swag\PayPal\AgenticCommerce\Exception\AgentHttpException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Expands logged objects, redacts request data, normalizes the exception and adds its error code, PayPal issue and debug ID,
 * the sales channel and Admin user of the request and `extra.area`, and drops `null` values; call site values win.
 * Handlers interpolate placeholders afterwards, so messages can use the added keys, e.g. `{errorCode}`.
 *
 * @internal
 */
#[Package('checkout')]
final class PayPalLogProcessor implements ProcessorInterface
{
    private const NAMESPACE_PREFIX = 'Swag\PayPal\\';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        try {
            $notNull = static fn (mixed $value): bool => $value !== null;
            $attributes = $this->requestStack->getMainRequest()?->attributes;
            $salesChannelId = $attributes?->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
            $requestContext = $attributes?->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
            $source = $requestContext instanceof Context ? $requestContext->getSource() : null;

            $context = LogContext::expand($record->context);
            $context = LogRedactor::redactContext($context);
            $context = $this->addExceptionDetails($context);
            $context = \array_filter($context, $notNull);

            $context += \array_filter([
                LogKey::SALES_CHANNEL_ID => \is_string($salesChannelId) ? $salesChannelId : null,
                LogKey::USER_ID => $source instanceof AdminApiSource ? $source->getUserId() : null,
            ], $notNull);

            return $record->with(context: $context, extra: [...$record->extra, LogKey::AREA => $this->detectArea()]);
        } catch (\Throwable) {
            // raw request data and objects must never pass: a formatter writes objects whole, including the buyer's address
            $context = \array_diff_key($record->context, LogContext::OBJECT_KEYS);
            foreach (LogRedactor::REDACTED_KEYS as $key) {
                if (\array_key_exists($key, $context)) {
                    $context[$key] = LogRedactor::REDACTED;
                }
            }

            return $record->with(context: $context);
        }
    }

    private function detectArea(): string
    {
        foreach (\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 40) as $frame) {
            $class = $frame['class'] ?? '';

            if (!\str_starts_with($class, self::NAMESPACE_PREFIX)
                || \str_starts_with($class, self::NAMESPACE_PREFIX . 'Util\\')) {
                continue;
            }

            return LogArea::forClass($class);
        }

        return LogArea::OTHER;
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array<array-key, mixed>
     */
    private function addExceptionDetails(array $context): array
    {
        $exception = $context[LogKey::EXCEPTION] ?? null;
        if (!$exception instanceof \Throwable) {
            return $context;
        }

        $context[LogKey::ERROR_CODE] ??= match (true) {
            $exception instanceof ShopwareHttpException => $exception->getErrorCode(),
            $exception instanceof ApiException => PayPalApiException::from($exception)->getErrorCode(),
            default => (new \ReflectionClass($exception))->getShortName(),
        };

        $context = [...$this->paypalError($exception), ...$context];
        $context[LogKey::EXCEPTION] = $this->normalizeException($exception);

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    private function paypalError(\Throwable $exception): array
    {
        $apiException = null;
        $wrapper = null;
        for ($e = $exception; $e instanceof \Throwable; $e = $e->getPrevious()) {
            $apiException ??= $e instanceof ApiException ? $e : null;
            $wrapper ??= $e instanceof PayPalApiException ? $e : null;
        }

        if ($apiException !== null) {
            $issues = $apiException instanceof ErrorApiException ? $apiException->getDetails()->getIssues() : [];

            return [
                LogKey::HTTP_STATUS => $apiException->getStatusCode(),
                LogKey::PAYPAL_ERROR => $apiException->getErrorCode(),
                LogKey::PAYPAL_ISSUES => $issues ?: null,
                LogKey::PAYPAL_DEBUG_ID => $apiException instanceof ErrorApiException ? $apiException->debugId : null,
            ];
        }

        if ($wrapper !== null) {
            return [
                LogKey::HTTP_STATUS => $wrapper->getStatusCode(),
                LogKey::PAYPAL_ERROR => $wrapper->getName(),
                LogKey::PAYPAL_ISSUES => $wrapper->getIssue() !== null ? [$wrapper->getIssue()] : null,
            ];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeException(\Throwable $e): array
    {
        // the SDK's message echoes the submitted value of a rejected field
        $normalized = ['class' => $e::class, 'message' => $e instanceof ApiException ? $e->getReason() : $e->getMessage()];

        if ($e instanceof ShopwareHttpException) {
            $normalized['errorCode'] = $e->getErrorCode();
            $normalized['parameters'] = LogRedactor::redactBody($e->getParameters());
        }

        if ($e instanceof AgentHttpException) {
            $normalized['details'] = LogRedactor::redactBody($e->getDetails()->jsonSerialize());
        }

        if ($e instanceof ConstraintViolationException) {
            foreach ($e->getViolations() as $violation) {
                $normalized['violations'][] = (string) $violation;
            }
        }

        $normalized['file'] = $e->getFile();
        $normalized['line'] = $e->getLine();

        // the SDK exception's facts are in the context already
        $previous = $e->getPrevious();
        if ($previous !== null && !$previous instanceof ApiException) {
            $normalized['previous'] = $this->normalizeException($previous);
        }

        return $normalized;
    }
}
