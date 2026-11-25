<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\StaticAnalyze\PHPStan\Rules;

use Monolog\Level;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ObjectType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\Log;

/**
 * Values must go through `Log::context()`, which redacts them, not into the message, and lines from `info` up need a log code.
 * PHPStan checks the context's keys and types itself, they are the parameters of `Log::context()`.
 * The POS run log isn't checked, it has its own logger and handler.
 *
 * @internal
 *
 * @implements Rule<MethodCall>
 */
#[Package('checkout')]
final class LogCallRule implements Rule
{
    private const NAMESPACE_PREFIX = 'Swag\\PayPal\\';
    private const IGNORED_NAMESPACE_PREFIXES = ['Swag\\PayPal\\DevOps\\', 'Swag\\PayPal\\Test\\'];
    private const POS_RUN_LOG_NAMESPACE_PREFIX = 'Swag\\PayPal\\Pos\\';
    private const POS_WEBHOOK_NAMESPACE_PREFIX = 'Swag\\PayPal\\Pos\\Webhook\\';

    private const LEVEL_METHODS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
    private const LEVELS_WITHOUT_CODE = ['debug'];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall || !$node->name instanceof Identifier) {
            return [];
        }

        $method = $node->name->toLowerString();
        if ($method !== 'log' && !\in_array($method, self::LEVEL_METHODS, true)) {
            return [];
        }

        $className = $scope->getClassReflection()?->getName();
        if ($className === null || !$this->isChecked($className)
            || !(new ObjectType(LoggerInterface::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        $args = $node->getArgs();
        if ($args === [] || \array_filter($args, static fn (Arg $arg) => $arg->unpack) !== []) {
            return [];
        }

        $levels = $method === 'log' ? $this->resolveLevels($args[0]->value, $scope) : [$method];
        $messageIndex = $method === 'log' ? 1 : 0;
        $context = ($args[$messageIndex + 1] ?? null)?->value;
        $errors = [];

        if (!isset($args[$messageIndex]) || !$this->isConstantMessage($args[$messageIndex]->value, $scope)) {
            $errors[] = $this->error(
                'The log message must be a static string with {placeholders}, put the values into Log::context(), which redacts them.',
                'swagPayPal.logCall.message',
                $node,
            );
        }

        if ($context !== null && !$this->isLogContextCall($context, $scope)) {
            $errors[] = $this->error('Build the log context with Log::context(), which redacts the values.', 'swagPayPal.logCall.context', $node);
        }

        // an unknown level may be "info" or higher
        $needsCode = $levels === null || \array_diff($levels, self::LEVELS_WITHOUT_CODE) !== [];
        if ($needsCode && !$this->hasCode($context, $scope)) {
            $errors[] = $this->error('Log calls from "info" up need Log::context(code: LogCode::X).', 'swagPayPal.logCall.code', $node);
        }

        return $errors;
    }

    private function isChecked(string $className): bool
    {
        if (!\str_starts_with($className, self::NAMESPACE_PREFIX)) {
            return false;
        }

        foreach (self::IGNORED_NAMESPACE_PREFIXES as $prefix) {
            if (\str_starts_with($className, $prefix)) {
                return false;
            }
        }

        return !\str_starts_with($className, self::POS_RUN_LOG_NAMESPACE_PREFIX)
            || \str_starts_with($className, self::POS_WEBHOOK_NAMESPACE_PREFIX);
    }

    /**
     * @return list<string>|null null if PHPStan doesn't know every possible level
     */
    private function resolveLevels(Expr $expr, Scope $scope): ?array
    {
        $type = $scope->getType($expr);

        $strings = $type->getConstantStrings();
        if ($strings !== []) {
            return \array_map(static fn (ConstantStringType $string): string => \strtolower($string->getValue()), $strings);
        }

        $cases = $type->getEnumCases();
        if ($cases !== [] && \array_filter($cases, static fn (EnumCaseObjectType $case): bool => $case->getClassName() !== Level::class) === []) {
            return \array_map(static fn (EnumCaseObjectType $case): string => \strtolower($case->getEnumCaseName()), $cases);
        }

        return null;
    }

    private function isConstantMessage(Expr $expr, Scope $scope): bool
    {
        if ($expr instanceof String_) {
            return true;
        }

        return ($expr instanceof ClassConstFetch || $expr instanceof ConstFetch)
            && \count($scope->getType($expr)->getConstantStrings()) === 1;
    }

    /**
     * @phpstan-assert-if-true StaticCall $expr
     */
    private function isLogContextCall(Expr $expr, Scope $scope): bool
    {
        return $expr instanceof StaticCall
            && $expr->class instanceof Name
            && $scope->resolveName($expr->class) === Log::class
            && $expr->name instanceof Identifier
            && $expr->name->toLowerString() === 'context';
    }

    private function hasCode(?Expr $context, Scope $scope): bool
    {
        if ($context === null || !$this->isLogContextCall($context, $scope)) {
            return false;
        }

        return \array_filter($context->getArgs(), static fn (Arg $arg) => $arg->name?->toString() === 'code') !== [];
    }

    private function error(string $message, string $identifier, Node $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->line($node->getStartLine())->build();
    }
}
