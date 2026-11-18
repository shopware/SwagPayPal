<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\LogCallRuleFixture;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;

class Calls
{
    public function __construct(private readonly LoggerInterface $logger, private readonly \stdClass $object)
    {
    }

    public function run(\Throwable $e, array $other, string $dynamic, ?\Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity $transaction = null, ?\Shopware\PayPalSDK\Struct\V2\Order $paypalOrder = null, ?\Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture $capture = null, string $level = 'debug', bool $flag = false): void
    {
        // valid
        $this->logger->debug('Debug without code');
        $this->logger->info('Literal', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->info('Spread', [...[LogKey::CODE => LogCode::API_REQUEST_FAILED], LogKey::EXCEPTION => $e]);
        $this->logger->warning('Spread left', [LogKey::CODE => LogCode::API_REQUEST_FAILED, ...$other]);
        $this->logger->warning('Spread right', [...$other, LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log(LogLevel::DEBUG, 'Log debug');
        $this->logger->log(LogLevel::INFO, 'Log info', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->critical('Nullable exception', [LogKey::CODE => LogCode::API_REQUEST_FAILED, LogKey::EXCEPTION => $e]);
        $this->logger->debug('Dynamic key', [$dynamic => 'value', \strtolower($dynamic) => 'value']);

        // invalid level
        $this->logger->alert('Alert', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log(LogLevel::EMERGENCY, 'Emergency', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);

        // invalid message
        $this->logger->debug('Concat ' . $dynamic);
        $this->logger->debug($dynamic);
        $this->logger->debug(\sprintf('Format %s', $dynamic));
        $this->logger->debug($e->getMessage());

        // missing code
        $this->logger->info('No context');
        $this->logger->warning('No code', [LogKey::PAYPAL_ORDER_ID => $dynamic]);
        $this->logger->error('Wrong code', [LogKey::CODE => 'API_REQUEST_FAILED']);
        $this->logger->error('Variable', $other);
        $this->logger->log(LogLevel::NOTICE, 'Log notice', [LogKey::PAYPAL_ORDER_ID => $dynamic]);

        // invalid objects
        $this->logger->debug('Object', [LogKey::ORDER_ID => $this->object]);
        $this->logger->debug('Wrong key', [LogKey::ERROR_CODE => $e]);
        $this->logger->info('Nested object', [...[LogKey::ORDER_ID => $this->object], LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->debug('Product outside Zettle', [LogKey::ORDER_NUMBER => $this->object]);

        // invalid message style
        $this->logger->debug('Trailing period.');
        $this->logger->debug('Two sentences. The second one');
        $this->logger->debug('Semicolon; clauses');
        $this->logger->info('Info with period.', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);

        // invalid context keys
        $this->logger->debug('Literal key', ['paypalOrderId' => $dynamic]);
        $this->logger->info('Literal code key', ['code' => LogCode::API_REQUEST_FAILED]);
        $this->logger->debug('Other constant', [LogCode::API_REQUEST_FAILED => $dynamic]);
        $this->logger->debug('Spread literal', [...['paypalOrderId' => $dynamic]]);
        $this->logger->debug('Literal exception key', ['exception' => $e]);

        // object keys
        $this->logger->debug('Object keys', [LogKey::ORDER_TRANSACTION => $transaction, LogKey::PAYPAL_ORDER => $paypalOrder, LogKey::PAYMENT => $capture, LogKey::WEBHOOK_EVENT => null]);
        $this->logger->debug('Wrong object', [LogKey::PAYPAL_ORDER => $transaction]);
        $this->logger->debug('Scalar', [LogKey::PAYMENT => $dynamic]);

        // log() is checked like the level methods
        $this->logger->log($level, 'Runtime level', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log(\Monolog\Level::Info, 'Enum level', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log($flag ? LogLevel::DEBUG : LogLevel::NOTICE, 'Either level', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log($level, 'Runtime level without code');
        $this->logger->log(\Monolog\Level::Notice, 'Enum level without code');
        $this->logger->log($flag ? LogLevel::DEBUG : LogLevel::INFO, 'Maybe info without code');
        $this->logger->log($flag ? LogLevel::CRITICAL : LogLevel::ALERT, 'Maybe alert', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log(\Monolog\Level::Emergency, 'Enum emergency', [LogKey::CODE => LogCode::API_REQUEST_FAILED]);
        $this->logger->log(LogLevel::DEBUG, 'Log concat ' . $dynamic);
        $this->logger->log(LogLevel::DEBUG, 'Log trailing period.');
        $this->logger->log(LogLevel::DEBUG, 'Log literal key', ['paypalOrderId' => $dynamic]);
        $this->logger->log(LogLevel::DEBUG, 'Log object', [LogKey::ORDER_ID => $this->object]);
    }
}
