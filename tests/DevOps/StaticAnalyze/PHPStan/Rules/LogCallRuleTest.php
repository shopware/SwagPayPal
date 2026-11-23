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
    private const ERROR_MESSAGE = 'The log message must be a static string with {placeholders}, put the values into Log::context(), which redacts them.';
    private const ERROR_CONTEXT = 'Build the log context with Log::context(), which redacts the values.';
    private const ERROR_CODE = 'Log calls from "info" up need Log::context(code: LogCode::X).';

    public function testLogCalls(): void
    {
        $this->analyse([__DIR__ . '/data/log-calls.php'], [
            [self::ERROR_MESSAGE, 32],
            [self::ERROR_MESSAGE, 33],
            [self::ERROR_MESSAGE, 34],
            [self::ERROR_MESSAGE, 35],
            [self::ERROR_CONTEXT, 38],
            [self::ERROR_CONTEXT, 39],
            [self::ERROR_CODE, 42],
            [self::ERROR_CODE, 43],
            [self::ERROR_CODE, 45],
            [self::ERROR_CODE, 46],
            [self::ERROR_CODE, 47],
            [self::ERROR_CODE, 48],
            [self::ERROR_CODE, 49],
            [self::ERROR_CODE, 50],
        ]);
    }

    public function testPosRunLogIsIgnored(): void
    {
        $this->analyse([__DIR__ . '/data/pos-log-calls.php'], []);
    }

    public function testPosWebhookFollowsTheGeneralRules(): void
    {
        $this->analyse([__DIR__ . '/data/pos-webhook-log-calls.php'], [
            [self::ERROR_CODE, 20],
            [self::ERROR_CONTEXT, 21],
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
