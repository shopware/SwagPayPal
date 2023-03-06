<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class LoggerMock implements LoggerInterface
{
    /**
     * @var array<array-key, mixed>[]
     */
    private array $logs = [];

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function alert($message, array $context = []): void
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function critical($message, array $context = []): void
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function error($message, array $context = []): void
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function warning($message, array $context = []): void
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function notice($message, array $context = []): void
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function info($message, array $context = []): void
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /**
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function debug($message, array $context = []): void
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * @param mixed  $level
     * @param string $message
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}
