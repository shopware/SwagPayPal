<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\LogCallRuleFixture;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

class Calls
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function run(\Throwable $e, array $other, string $dynamic, string $level = 'debug', bool $flag = false, ?LoggerInterface $nullableLogger = null): void
    {
        // valid
        $this->logger->debug('Debug without context');
        $this->logger->info('Code', Log::context(code: LogCode::API_REQUEST_FAILED));
        $this->logger->warning('Shared values', Log::context(...[Log::PAYPAL_ORDER_ID => $dynamic], code: LogCode::API_REQUEST_FAILED, exception: $e));
        $this->logger->log(LogLevel::DEBUG, 'Log debug');
        $this->logger->log(LogLevel::INFO, 'Log info', Log::context(code: LogCode::API_REQUEST_FAILED));
        $nullableLogger?->debug('Nullsafe debug', Log::context(paypalOrderId: $dynamic));

        // values in the message
        $this->logger->debug('Concat ' . $dynamic);
        $this->logger->debug($dynamic);
        $this->logger->debug(\sprintf('Format %s', $dynamic));
        $this->logger->debug($e->getMessage());

        // context not built by Log::context()
        $this->logger->debug('Array', [Log::PAYPAL_ORDER_ID => $dynamic]);
        $this->logger->debug('Variable', $other);

        // missing code
        $this->logger->info('No context');
        $this->logger->warning('No code', Log::context(paypalOrderId: $dynamic));
        $this->logger->error('Code as a variable', Log::context(code: $dynamic));
        $this->logger->error('Code in shared values', Log::context(...[Log::CODE => LogCode::API_REQUEST_FAILED]));
        $this->logger->log(LogLevel::NOTICE, 'Log notice', Log::context(paypalOrderId: $dynamic));
        $this->logger->log($level, 'Runtime level without code');
        $this->logger->log(\Monolog\Level::Notice, 'Enum level without code');
        $this->logger->log($flag ? LogLevel::DEBUG : LogLevel::INFO, 'Maybe info without code');
        $nullableLogger?->info('Nullsafe without code');
    }
}
