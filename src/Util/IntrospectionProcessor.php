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
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Client\AbstractClient as PosAbstractClient;
use Swag\PayPal\RestApi\Client\AbstractClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @phpstan-type Trace array{file?: string, line?: int, function?: string, class?: string, type?: string}
 */
#[Package('checkout')]
class IntrospectionProcessor implements ProcessorInterface
{
    private Level $level;

    private const SKIP_FUNCTIONS = [
        'call_user_func',
        'call_user_func_array',
    ];

    /**
     * @param string|int|Level $level The minimum logging level at which this Processor will be triggered
     *
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level|LogLevel::* $level
     */
    public function __construct(int|string|Level $level = Level::Error)
    {
        $this->level = Logger::toMonologLevel($level);
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // return if the level is not high enough
        if ($record->level->isLowerThan($this->level)) {
            return $record;
        }

        /** @var Trace[] $traces */
        $traces = $this->getBacktrace();

        $context = [];
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
                $context['client'] ??= $this->traceToClassString($trace);

                continue;
            }

            if ($this->isResource($trace)) {
                $context['resource'] ??= $this->traceToClassString($trace);

                continue;
            }

            if ($this->isController($trace)) {
                $context['controller'] ??= $this->traceToClassString($trace);

                continue;
            }

            break;
        }

        // Try finding any controller
        foreach (\array_slice($traces, $index) as $trace) {
            if (isset($context['controller'])) {
                break;
            }

            if ($this->isController($trace)) {
                $context['controller'] = $this->traceToClassString($trace);
            }
        }

        $record->extra = [
            ...$record->extra,
            ...$context,
            'file' => $traces[$index - 1]['file'] ?? null,
            'line' => $traces[$index - 1]['line'] ?? null,
            'class' => $traces[$index]['class'] ?? null,
            'function' => $traces[$index]['function'] ?? null,
        ];

        return $record;
    }

    /**
     * @return Trace
     */
    protected function getBacktrace(): array
    {
        $traces = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);

        // remove getBacktrace(), __invoke(), 2x logger call
        \array_splice($traces, 0, 3);

        return $traces;
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
        return \str_contains($trace['class'] ?? '', AbstractClient::class)
            || \str_contains($trace['class'] ?? '', PosAbstractClient::class);
    }

    /**
     * @param Trace $trace
     */
    private function isClient(array $trace): bool
    {
        return \is_subclass_of($trace['class'] ?? '', AbstractClient::class)
            || \is_subclass_of($trace['class'] ?? '', PosAbstractClient::class);
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
        return \str_contains($trace['class'] ?? '', 'Swag\PayPal');
    }
}
