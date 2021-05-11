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
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Util\Log\LoggerFactory;

class LoggerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const LOGGER_PREFIX = 'swag_paypal_logger_test';

    /**
     * @var string
     */
    private $logsDir;

    public function setUp(): void
    {
        /** @var string $logsDir */
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->logsDir = $logsDir;

        while (($file = $this->getFirstLogFile()) !== null) {
            \unlink($file);
        }
    }

    public function tearDown(): void
    {
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $systemConfigService->delete(\sprintf('%sloggingLevel', SettingsService::SYSTEM_CONFIG_DOMAIN));
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

        static::assertSame(!$shouldCreateLog, $this->getFirstLogFile() === null);
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
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $systemConfigService->set(\sprintf('%sloggingLevel', SettingsService::SYSTEM_CONFIG_DOMAIN), $level);

        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $this->getContainer()->get(LoggerFactory::class);
        $loggerFactory->setLogLevel($systemConfigService);

        return $loggerFactory->createRotating(self::LOGGER_PREFIX);
    }
}
