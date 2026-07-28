<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Validation\CartTokenValidator;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CartTokenValidator::class)]
class CartTokenValidatorTest extends TestCase
{
    #[DataProvider('dataProviderCartToken')]
    public function testCartToken(string $token, ?string $id): void
    {
        if (!$id) {
            $this->expectException(AgentException::class);
            $this->expectExceptionMessage('Cart ID format is invalid. Expected format: CART-[a-zA-Z0-9]{32}');
        }

        $extracted = CartTokenValidator::validateCartToken($token);

        static::assertSame($id, $extracted);
    }

    public static function dataProviderCartToken(): array
    {
        return [
            ['Cart-123456789', null],
            ['CART_123456789', null],
            ['CART1234567890', null],
            ['1234-CART-1234', null],
            ['CART-.,:', null],
            ['CART-123456789', '123456789'],
            ['CART-ABC123abc', 'ABC123abc'],
        ];
    }
}
