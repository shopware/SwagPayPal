<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Validation;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
final class HasScopes implements Constraint
{
    /**
     * @param list<string> $expectedScopes
     */
    public function __construct(private readonly array $expectedScopes)
    {
    }

    public function assert(Token $token): void
    {
        if (!$token instanceof UnencryptedToken) {
            throw ConstraintViolation::error('You should pass a plain token', $this);
        }

        $claims = $token->claims();

        if (!$claims->has('scope')) {
            throw ConstraintViolation::error('The token does not have the claim "scope"', $this);
        }

        $scopes = $claims->get('scope');

        if (!\is_array($scopes)) {
            throw ConstraintViolation::error('The claim "scope" is not an array', $this);
        }

        $missingScopes = \array_diff($this->expectedScopes, $scopes);

        if ($missingScopes !== []) {
            throw ConstraintViolation::error('The token does not contain the required scopes: "' . \implode(', ', $missingScopes) . '"', $this);
        }
    }
}
