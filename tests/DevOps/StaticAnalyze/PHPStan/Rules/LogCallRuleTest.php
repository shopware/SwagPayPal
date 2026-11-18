<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\DevOps\StaticAnalyze\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\DevOps\StaticAnalyze\PHPStan\Rules\LogCallRule;

/**
 * @internal
 *
 * @extends RuleTestCase<LogCallRule>
 */
#[Package('checkout')]
#[CoversClass(LogCallRule::class)]
class LogCallRuleTest extends RuleTestCase
{
    private const ERROR_LEVEL = 'Do not log with level "%s"; use "critical" at most.';
    private const ERROR_MESSAGE = 'The log message must be a static string literal with {placeholders}; put the values into the context.';
    private const ERROR_CODE = 'Log calls from "info" up need \'code\' => LogCode::X in the context.';
    private const ERROR_STYLE = 'Log messages are one sentence without a trailing period; join clauses with a comma.';
    private const ERROR_KEY = 'Use a LogKey constant as log context key.';
    private const ERROR_OBJECT = 'Do not put objects into the log context%s; use scalars, an object key of LogContext::OBJECT_KEYS or the exception under \'exception\'.';
    private const ERROR_OBJECT_KEY = 'The log context key "%s" takes %s.';

    public function testLogCalls(): void
    {
        $this->analyse([__DIR__ . '/data/log-calls.php'], [
            [\sprintf(self::ERROR_LEVEL, 'alert'), 35],
            [\sprintf(self::ERROR_LEVEL, 'emergency'), 36],
            [self::ERROR_MESSAGE, 39],
            [self::ERROR_MESSAGE, 40],
            [self::ERROR_MESSAGE, 41],
            [self::ERROR_MESSAGE, 42],
            [self::ERROR_CODE, 45],
            [self::ERROR_CODE, 46],
            [self::ERROR_CODE, 47],
            [self::ERROR_CODE, 48],
            [self::ERROR_CODE, 49],
            [\sprintf(self::ERROR_OBJECT, ' (key "orderId")'), 52],
            [\sprintf(self::ERROR_OBJECT, ' (key "errorCode")'), 53],
            [\sprintf(self::ERROR_OBJECT, ' (key "orderId")'), 54],
            [\sprintf(self::ERROR_OBJECT, ' (key "orderNumber")'), 55],
            [self::ERROR_STYLE, 58],
            [self::ERROR_STYLE, 59],
            [self::ERROR_STYLE, 60],
            [self::ERROR_STYLE, 61],
            [self::ERROR_KEY, 64],
            [self::ERROR_KEY, 65],
            [self::ERROR_KEY, 66],
            [self::ERROR_KEY, 67],
            [self::ERROR_KEY, 68],
            [\sprintf(self::ERROR_OBJECT_KEY, 'paypalOrder', 'Order'), 72],
            [\sprintf(self::ERROR_OBJECT_KEY, 'payment', 'Capture|Refund|Authorization'), 73],
            [self::ERROR_CODE, 79],
            [self::ERROR_CODE, 80],
            [self::ERROR_CODE, 81],
            [\sprintf(self::ERROR_LEVEL, 'alert'), 82],
            [\sprintf(self::ERROR_LEVEL, 'emergency'), 83],
            [self::ERROR_MESSAGE, 84],
            [self::ERROR_STYLE, 85],
            [self::ERROR_KEY, 86],
            [\sprintf(self::ERROR_OBJECT, ' (key "orderId")'), 87],
        ]);
    }

    public function testZettleRunLogNeedsNoCodeAndOnlyTheExceptionKeyAndMayLogProducts(): void
    {
        $this->analyse([__DIR__ . '/data/pos-log-calls.php'], [
            [self::ERROR_KEY, 23],
            [\sprintf(self::ERROR_OBJECT, ' (key "order")'), 24],
            [\sprintf(self::ERROR_LEVEL, 'alert'), 25],
            [\sprintf(self::ERROR_OBJECT, ' (key "paypalOrder")'), 26],
        ]);
    }

    public function testZettleWebhookFollowsTheGeneralRules(): void
    {
        $this->analyse([__DIR__ . '/data/pos-webhook-log-calls.php'], [
            [self::ERROR_CODE, 20],
            [self::ERROR_KEY, 21],
            [\sprintf(self::ERROR_OBJECT, ' (key "product")'), 21],
        ]);
    }

    public function testDevOpsIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/data/devops-log-calls.php'], []);
    }

    public function analyse(array $files, array $expectedErrors): void
    {
        foreach ($files as $file) {
            // PHPStan's reflection needs the fixture classes to be autoloadable
            require_once $file;
        }

        parent::analyse($files, $expectedErrors);
    }

    protected function getRule(): Rule
    {
        return new LogCallRule();
    }
}
