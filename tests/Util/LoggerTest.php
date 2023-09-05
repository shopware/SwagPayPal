<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Log\LoggerFactory;

/**
 * @internal
 *
 * @deprecated tag:v8.0.0 - Will be removed.
 */
class LoggerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const LOGGER_PREFIX = 'swag_paypal_logger_test';

    private string $logsDir;

    protected function setUp(): void
    {
        /** @var string $logsDir */
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->logsDir = $logsDir;

        while (($file = $this->getFirstLogFile()) !== null) {
            \unlink($file);
        }
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get(SystemConfigService::class)->delete(Settings::LOGGING_LEVEL);
    }

    public function dataProviderLevel(): array
    {
        return [
            [
                Logger::WARNING,
                Logger::WARNING,
                true,
            ],
            [
                Logger::WARNING,
                Logger::INFO,
                false,
            ],
            [
                Logger::DEBUG,
                Logger::WARNING,
                true,
            ],
            [
                Logger::DEBUG,
                Logger::DEBUG,
                true,
            ],
            [
                ['invalidLoggingValue'],
                Logger::DEBUG,
                false,
            ],
            [
                ['invalidLoggingValue'],
                Logger::WARNING,
                true,
            ],
            [
                null,
                Logger::DEBUG,
                false,
            ],
            [
                null,
                Logger::WARNING,
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderLevel
     *
     * @param int|array|null $setLevel
     */
    public function testLog($setLevel, int $debugLevel, bool $shouldCreateLog): void
    {
        static::assertNull($this->getFirstLogFile());

        $this->createLogger($setLevel)->log($debugLevel, 'Logger Test');

        if ($shouldCreateLog) {
            static::assertNotNull($this->getFirstLogFile());
        } else {
            static::assertNull($this->getFirstLogFile());
        }
    }

    private function getFirstLogFile(): ?string
    {
        $files = \scandir($this->logsDir);
        if (!\is_array($files)) {
            return null;
        }

        foreach ($files as $logFileName) {
            if (\mb_strpos($logFileName, self::LOGGER_PREFIX) === 0) {
                return \sprintf('%s%s%s', $this->logsDir, \DIRECTORY_SEPARATOR, $logFileName);
            }
        }

        return null;
    }

    /**
     * @param int|array|null $level
     */
    private function createLogger($level): LoggerInterface
    {
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $systemConfigService->set(Settings::LOGGING_LEVEL, $level);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $loggerFactory = new LoggerFactory($logsDir . \DIRECTORY_SEPARATOR . '%s_test.log');
        $loggerFactory->setLogLevel($systemConfigService);

        return $loggerFactory->createRotating(self::LOGGER_PREFIX);
    }
}
