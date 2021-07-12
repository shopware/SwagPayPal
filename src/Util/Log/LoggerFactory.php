<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;

class LoggerFactory
{
    protected const DEFAULT_LEVEL = Logger::WARNING;
    protected const ALLOWED_LOG_LEVEL = [
        Logger::DEBUG,
        Logger::INFO,
        Logger::NOTICE,
        Logger::WARNING,
        Logger::ERROR,
        Logger::CRITICAL,
        Logger::ALERT,
        Logger::EMERGENCY,
    ];
    private const LOG_FORMAT = "[%datetime%] %channel%.%level_name%: %extra.class%::%extra.function% (%extra.line%): %message% %context% %extra%\n";

    /**
     * @phpstan-var Logger::DEBUG|Logger::INFO|Logger::NOTICE|Logger::WARNING|Logger::ERROR|Logger::CRITICAL|Logger::ALERT|Logger::EMERGENCY
     */
    protected int $logLevel = self::DEFAULT_LEVEL;

    private string $rotatingFilePathPattern;

    private int $defaultFileRotationCount;

    public function __construct(string $rotatingFilePathPattern, int $defaultFileRotationCount = 14)
    {
        $this->rotatingFilePathPattern = $rotatingFilePathPattern;
        $this->defaultFileRotationCount = $defaultFileRotationCount;
    }

    public function setLogLevel(SystemConfigService $systemConfigService): void
    {
        $this->logLevel = self::DEFAULT_LEVEL;

        try {
            $setting = $systemConfigService->getInt(Settings::LOGGING_LEVEL);
            if (\in_array($setting, self::ALLOWED_LOG_LEVEL, true)) {
                $this->logLevel = $setting;
            }
        } catch (\Throwable $e) {
            // use default level then
        }
    }

    public function createRotating(string $filePrefix): LoggerInterface
    {
        $filepath = \sprintf($this->rotatingFilePathPattern, $filePrefix);

        $logger = new Logger($filePrefix);
        $handler = new RotatingFileHandler($filepath, $this->defaultFileRotationCount, $this->logLevel);
        $handler->setFormatter(new LineFormatter(self::LOG_FORMAT));
        $logger->pushHandler($handler);
        $logger->pushProcessor(new PsrLogMessageProcessor(null, true));
        $logger->pushProcessor(new IntrospectionProcessor($this->logLevel));
        if ($this->logLevel < Logger::WARNING) {
            $logger->pushProcessor(new WebProcessor(
                null,
                [
                    'url' => 'REQUEST_URI',
                    'http_method' => 'REQUEST_METHOD',
                    'server' => 'SERVER_NAME',
                ]
            ));
        }

        return $logger;
    }
}
