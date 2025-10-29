<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\Validation;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\ConstraintViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Validation\HasScopes;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(HasScopes::class)]
class HasScopesTest extends TestCase
{
    public function testAssertWithNonUnencryptedToken(): void
    {
        $token = new class implements Token {
            public function headers(): Token\DataSet
            {
                return new Token\DataSet([], 'foo');
            }

            public function isPermittedFor(string $audience): bool
            {
                return false;
            }

            public function isIdentifiedBy(string $id): bool
            {
                return false;
            }

            public function isRelatedTo(string $subject): bool
            {
                return false;
            }

            public function hasBeenIssuedBy(string ...$issuers): bool
            {
                return false;
            }

            public function hasBeenIssuedBefore(\DateTimeInterface $now): bool
            {
                return false;
            }

            public function isMinimumTimeBefore(\DateTimeInterface $now): bool
            {
                return false;
            }

            public function isExpired(\DateTimeInterface $now): bool
            {
                return false;
            }

            public function toString(): string
            {
                return 'foo';
            }
        };

        $constraint = new HasScopes(['scope1', 'scope2']);

        static::expectExceptionObject(ConstraintViolation::error('You should pass a plain token', $constraint));

        $constraint->assert($token);
    }

    public function testAssertWithMissingScopeClaim(): void
    {
        $token = new Token\Plain(
            new Token\DataSet([], 'foo'),
            new Token\DataSet([], 'foo'),
            new Token\Signature('foo', 'foo')
        );

        $constraint = new HasScopes(['scope1', 'scope2']);

        static::expectExceptionObject(ConstraintViolation::error('The token does not have the claim "scope"', $constraint));

        $constraint->assert($token);
    }

    public function testAssertWithNonArrayScopeClaim(): void
    {
        $token = new Token\Plain(
            new Token\DataSet([], 'foo'),
            new Token\DataSet(['scope' => 'non-array'], 'foo'),
            new Token\Signature('foo', 'foo')
        );

        $constraint = new HasScopes(['scope1', 'scope2']);

        static::expectExceptionObject(ConstraintViolation::error('The claim "scope" is not an array', $constraint));

        $constraint->assert($token);
    }

    public function testAssertWithMissingScope(): void
    {
        $token = new Token\Plain(
            new Token\DataSet([], 'foo'),
            new Token\DataSet(['scope' => ['scope1']], 'foo'),
            new Token\Signature('foo', 'foo')
        );

        $constraint = new HasScopes(['scope1', 'scope2', 'scope3']);

        static::expectExceptionObject(ConstraintViolation::error('The token does not contain the required scopes: "scope2, scope3"', $constraint));

        $constraint->assert($token);
    }

    public function testAssert(): void
    {
        static::expectNotToPerformAssertions();

        $token = new Token\Plain(
            new Token\DataSet([], 'foo'),
            new Token\DataSet(['scope' => ['scope1', 'scope2']], ''),
            new Token\Signature('foo', 'foo')
        );

        $constraint = new HasScopes(['scope1', 'scope2']);

        $constraint->assert($token);
    }
}
