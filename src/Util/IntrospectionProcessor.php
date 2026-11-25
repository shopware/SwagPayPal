<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Psr\Log\LogLevel;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\PayPalSDK\Contract\Gateway\GatewayInterface;
use Swag\PayPal\Pos\Client\AbstractClient as PosAbstractClient;
use Swag\PayPal\Util\Log\Log;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds where a log line comes from: the request's sales channel, Admin user, method, host and route, but not its URL,
 * as paths and queries carry tokens. From the given level up, as the backtrace is expensive, also the calling file, line,
 * class and function and the PayPal client, gateway, resource and controller.
 *
 * @phpstan-type Trace array{file?: string, line?: int, function?: string, class?: string, type?: string, args?: array<mixed>, object?: object}
 */
#[Package('checkout')]
class IntrospectionProcessor implements ProcessorInterface
{
    private const SKIP_FUNCTIONS = [
        'call_user_func',
        'call_user_func_array',
    ];

    private Level $level;

    /**
     * @param int|string|Level|LogLevel::* $level
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function __construct(
        int|string|Level $level = Level::Error,
        private readonly ?RequestStack $requestStack = null,
    ) {
        $this->level = Logger::toMonologLevel($level);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record = $this->addRequest($record);

        // return if the level is not high enough
        if ($record->level->isLowerThan($this->level)) {
            return $record;
        }

        $traces = $this->getBacktrace();

        $extra = [];
        $index = 1;
        for (; $index < \count($traces); ++$index) {
            $trace = $traces[$index];

            if (isset($trace['function']) && \in_array($trace['function'], self::SKIP_FUNCTIONS, true)) {
                continue;
            }

            if (!$this->isPayPalClass($trace)) {
                break;
            }

            if ($this->isAbstractClient($trace)) {
                continue;
            }

            if ($this->isClient($trace)) {
                $extra['client'] ??= $this->traceToClassString($trace);

                continue;
            }

            if ($this->isGateway($trace)) {
                $extra['gateway'] ??= $this->traceToClassString($trace);

                continue;
            }

            if ($this->isResource($trace)) {
                $extra['resource'] ??= $this->traceToClassString($trace);

                continue;
            }

            if ($this->isController($trace)) {
                $extra['controller'] ??= $this->traceToClassString($trace);

                continue;
            }

            break;
        }

        // Try finding any controller
        foreach (\array_slice($traces, $index) as $trace) {
            if (isset($extra['controller'])) {
                break;
            }

            if ($this->isController($trace)) {
                $extra['controller'] = $this->traceToClassString($trace);
            }
        }

        return $record->with(...[
            'extra' => [
                ...$record->extra,
                ...$extra,
                'file' => $traces[$index - 1]['file'] ?? null,
                'line' => $traces[$index - 1]['line'] ?? null,
                'class' => $traces[$index]['class'] ?? null,
                'function' => $traces[$index]['function'] ?? null,
            ],
        ]);
    }

    /**
     * @return array<int, Trace>
     */
    protected function getBacktrace(): array
    {
        $traces = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);

        // remove getBacktrace(), __invoke(), 2x logger call
        \array_splice($traces, 0, 3);

        return $traces;
    }

    private function addRequest(LogRecord $record): LogRecord
    {
        $request = $this->requestStack?->getMainRequest();
        if ($request === null) {
            return $record;
        }

        $requestContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
        $source = $requestContext instanceof Context ? $requestContext->getSource() : null;
        $isString = static fn (mixed $value): bool => \is_string($value);

        return $record->with(
            context: $record->context + \array_filter([
                Log::SALES_CHANNEL_ID => $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID),
                Log::USER_ID => $source instanceof AdminApiSource ? $source->getUserId() : null,
            ], $isString),
            extra: [...$record->extra, ...\array_filter([
                'http_method' => $request->server->get('REQUEST_METHOD'),
                'server' => $request->server->get('SERVER_NAME'),
                Log::ROUTE => $request->attributes->get('_route'),
            ], $isString)],
        );
    }

    /**
     * @param Trace $trace
     */
    private function traceToClassString(array $trace): string
    {
        return ($trace['class'] ?? '') . ($trace['type'] ?? '::') . ($trace['function'] ?? '');
    }

    /**
     * @param Trace $trace
     */
    private function isAbstractClient(array $trace): bool
    {
        return \str_contains($trace['class'] ?? '', PosAbstractClient::class);
    }

    /**
     * @param Trace $trace
     */
    private function isGateway(array $trace): bool
    {
        return \is_subclass_of($trace['class'] ?? '', GatewayInterface::class);
    }

    /**
     * @param Trace $trace
     */
    private function isClient(array $trace): bool
    {
        return \is_subclass_of($trace['class'] ?? '', PosAbstractClient::class);
    }

    /**
     * @param Trace $trace
     */
    private function isResource(array $trace): bool
    {
        return \str_ends_with($trace['class'] ?? '', 'Resource')
            && \str_contains($trace['class'] ?? '', '\Resource\\');
    }

    /**
     * @param Trace $trace
     */
    private function isController(array $trace): bool
    {
        return \is_subclass_of($trace['class'] ?? '', AbstractController::class);
    }

    /**
     * @param Trace $trace
     */
    private function isPayPalClass(array $trace): bool
    {
        return \str_contains($trace['class'] ?? '', 'Swag\PayPal')
            || \str_starts_with($trace['class'] ?? '', 'Shopware\PayPalSDK');
    }
}
