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
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
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
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\TypeCombinator;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogContext;
use Swag\PayPal\Util\Log\LogKey;

/**
 * Checks every log call of the plugin: level, static message, log code from `info` up, `LogKey` context keys and no objects outside the object keys.
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
    private const POS_NAMESPACE_PREFIX = 'Swag\\PayPal\\Pos\\';
    private const POS_WEBHOOK_NAMESPACE_PREFIX = 'Swag\\PayPal\\Pos\\Webhook\\';

    private const LEVEL_METHODS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
    private const LEVELS_WITH_CODE = ['info', 'notice', 'warning', 'error', 'critical'];
    private const FORBIDDEN_LEVELS = ['alert', 'emergency'];

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
        if ($className === null || !$this->isChecked($className)) {
            return [];
        }

        if (!(new ObjectType(LoggerInterface::class))->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        $args = $node->getArgs();
        if ($args === [] || \array_filter($args, static fn (Arg $arg) => $arg->unpack) !== []) {
            return [];
        }

        if ($method === 'log') {
            $levels = $this->resolveLevels($args[0]->value, $scope);
            $messageIndex = 1;
        } else {
            $levels = [$method];
            $messageIndex = 0;
        }

        $errors = [];

        foreach (\array_intersect($levels ?? [], self::FORBIDDEN_LEVELS) as $level) {
            $errors[] = $this->error(
                \sprintf('Do not log with level "%s"; use "critical" at most.', $level),
                'swagPayPal.logCall.level',
                $node,
            );
        }

        if (!isset($args[$messageIndex]) || !$this->isConstantMessage($args[$messageIndex]->value, $scope)) {
            $errors[] = $this->error(
                'The log message must be a static string literal with {placeholders}; put the values into the context.',
                'swagPayPal.logCall.message',
                $node,
            );
        } elseif ($this->hasBadMessageStyle($args[$messageIndex]->value, $scope)) {
            $errors[] = $this->error(
                'Log messages are one sentence without a trailing period; join clauses with a comma.',
                'swagPayPal.logCall.messageStyle',
                $node,
            );
        }

        $context = ($args[$messageIndex + 1] ?? null)?->value;

        // an unknown level may be "info" or higher
        $needsCode = $levels === null || \array_intersect($levels, self::LEVELS_WITH_CODE) !== [];
        if ($needsCode && !$this->isInPosRunLog($className)
            && ($context === null || !$this->containsCode($context, $scope))) {
            $errors[] = $this->error(
                'Log calls from "info" up need \'code\' => LogCode::X in the context.',
                'swagPayPal.logCall.code',
                $node,
            );
        }

        if ($context !== null) {
            $isPosRunLog = $this->isInPosRunLog($className);
            foreach ($this->collectArrays($context) as $array) {
                foreach ($array->items as $item) {
                    if ($item->unpack) {
                        continue;
                    }

                    // the run log has its own placeholder keys, but its handler reads the exception by LogKey
                    if ($this->isInvalidKey($item, $scope)
                        && (!$isPosRunLog || $this->resolveKey($item, $scope) === LogKey::EXCEPTION)) {
                        $errors[] = $this->error(
                            'Use a LogKey constant as log context key.',
                            'swagPayPal.logCall.contextKey',
                            $item,
                        );
                    }

                    $key = $this->resolveKey($item, $scope);
                    $valueType = TypeCombinator::removeNull($scope->getType($item->value));

                    // the run log has no processor that turns the objects into scalars
                    if (!$isPosRunLog && $key !== null && isset(LogContext::OBJECT_KEYS[$key])) {
                        $classes = LogContext::OBJECT_KEYS[$key];
                        if (!TypeCombinator::union(...\array_map(static fn (string $class) => new ObjectType($class), $classes))->isSuperTypeOf($valueType)->yes()) {
                            $errors[] = $this->error(
                                \sprintf(
                                    'The log context key "%s" takes %s.',
                                    $key,
                                    \implode('|', \array_map(static fn (string $class) => \basename(\str_replace('\\', '/', $class)), $classes)),
                                ),
                                'swagPayPal.logCall.objectKey',
                                $item,
                            );
                        }

                        continue;
                    }

                    if (!(new ObjectWithoutClassType())->isSuperTypeOf($valueType)->yes()) {
                        continue;
                    }

                    if ($this->isAllowedObject($key, $valueType, $className)) {
                        continue;
                    }

                    $errors[] = $this->error(
                        \sprintf('Do not put objects into the log context%s; use scalars, an object key of LogContext::OBJECT_KEYS or the exception under \'exception\'.', $key === null ? '' : \sprintf(' (key "%s")', $key)),
                        'swagPayPal.logCall.contextObject',
                        $item,
                    );
                }
            }
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

        return true;
    }

    private function isInPosRunLog(string $className): bool
    {
        return \str_starts_with($className, self::POS_NAMESPACE_PREFIX)
            && !\str_starts_with($className, self::POS_WEBHOOK_NAMESPACE_PREFIX);
    }

    private function isAllowedObject(?string $key, \PHPStan\Type\Type $type, string $className): bool
    {
        if ($key === 'exception' && (new ObjectType(\Throwable::class))->isSuperTypeOf($type)->yes()) {
            return true;
        }

        return $key === 'product' && $this->isInPosRunLog($className);
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

        if (!$expr instanceof ClassConstFetch && !$expr instanceof ConstFetch) {
            return false;
        }

        return \count($scope->getType($expr)->getConstantStrings()) === 1;
    }

    private function hasBadMessageStyle(Expr $expr, Scope $scope): bool
    {
        $strings = $scope->getType($expr)->getConstantStrings();
        if (\count($strings) !== 1) {
            return false;
        }

        $message = $strings[0]->getValue();

        return \str_ends_with($message, '.') || \str_contains($message, '. ') || \str_contains($message, '; ');
    }

    private function resolveKey(ArrayItem $item, Scope $scope): ?string
    {
        if ($item->key === null) {
            return null;
        }

        $strings = $scope->getType($item->key)->getConstantStrings();

        return \count($strings) === 1 ? $strings[0]->getValue() : null;
    }

    private function isInvalidKey(ArrayItem $item, Scope $scope): bool
    {
        $key = $item->key;
        if ($key instanceof String_) {
            return true;
        }

        if ($key instanceof ClassConstFetch) {
            return !$key->class instanceof Name || $scope->resolveName($key->class) !== LogKey::class;
        }

        return $key instanceof ConstFetch;
    }

    private function containsCode(Expr $expr, Scope $scope): bool
    {
        foreach ($this->collectArrays($expr) as $array) {
            foreach ($array->items as $item) {
                if ($item->unpack || $this->resolveKey($item, $scope) !== 'code') {
                    continue;
                }

                if ($item->value instanceof ClassConstFetch
                    && $item->value->class instanceof Name
                    && $scope->resolveName($item->value->class) === LogCode::class) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Includes arrays spread into the literal.
     *
     * @return list<Array_>
     */
    private function collectArrays(Expr $expr): array
    {
        if ($expr instanceof Array_) {
            $arrays = [$expr];
            foreach ($expr->items as $item) {
                if ($item->unpack) {
                    \array_push($arrays, ...$this->collectArrays($item->value));
                }
            }

            return $arrays;
        }

        return [];
    }

    private function error(string $message, string $identifier, Node $node): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->identifier($identifier)
            ->line($node->getStartLine())
            ->build();
    }
}
