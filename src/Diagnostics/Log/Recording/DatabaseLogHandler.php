<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Log\Recording;

use Doctrine\DBAL\Connection;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Diagnostics\Log\DataAbstractionLayer\LogLineDefinition;
use Swag\PayPal\Setting\Settings;

/**
 * @internal
 */
#[Package('checkout')]
class DatabaseLogHandler extends AbstractProcessingHandler
{
    private const JSON_FLAGS = \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRESERVE_ZERO_FRACTION | \JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct(
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService,
        private readonly bool $enabled,
    ) {
        parent::__construct();

        $this->pushProcessor(new PsrLogMessageProcessor());
    }

    public function isHandling(LogRecord $record): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $setting = $this->systemConfigService->get(Settings::LOG_LEVEL);
        } catch (\Throwable) {
            // logging must not fail when the config can't be read
            return false;
        }

        $setting ??= Settings::DEFAULT_VALUES[Settings::LOG_LEVEL];

        // 0 turns the logs off
        $threshold = \is_numeric($setting) ? Level::tryFrom((int) $setting) : null;

        return $threshold?->includes($record->level) ?? false;
    }

    protected function write(LogRecord $record): void
    {
        /** @var array{context?: array<mixed>, extra?: array<mixed>} $formatted */
        $formatted = $record->formatted;

        try {
            $this->connection->insert(LogLineDefinition::ENTITY_NAME, [
                'id' => Uuid::randomBytes(),
                'level' => $record->level->value,
                'message' => $record->message,
                'context' => $this->encode($formatted['context'] ?? []),
                'extra' => $this->encode($formatted['extra'] ?? []),
                'created_at' => $record->datetime->setTimezone(new \DateTimeZone('UTC'))->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (\Throwable) {
            // a lost line must not fail the request that logged it
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter();
    }

    /**
     * @param array<mixed> $value
     */
    private function encode(array $value): ?string
    {
        return $value === [] ? null : \json_encode($value, self::JSON_FLAGS);
    }
}
