<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(MerchantIntegrations::class)]
class MerchantIntegrationsTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('provideMissingProducts')]
    public function testMissingProducts(array $data): void
    {
        $integrations = (new MerchantIntegrations())->assign(['merchant_id' => 'some-merchant-id', ...$data]);

        static::assertCount(0, $integrations->getProducts());
        static::assertNull($integrations->getSpecificProduct('PAYMENT_METHODS'));
        static::assertArrayNotHasKey('products', $integrations->jsonSerialize());
    }

    public static function provideMissingProducts(): \Generator
    {
        yield 'missing' => [[]];
        yield 'empty' => [['products' => []]];
    }

    public function testSpecificProduct(): void
    {
        $integrations = (new MerchantIntegrations())->assign([
            'products' => [
                ['name' => 'PPCP_CUSTOM', 'vetting_status' => 'SUBSCRIBED'],
                ['name' => 'PAYMENT_METHODS'],
            ],
        ]);

        $product = $integrations->getSpecificProduct('PAYMENT_METHODS');
        static::assertNotNull($product);
        static::assertNull($product->getVettingStatus());
        static::assertNull($integrations->getSpecificProduct('ADVANCED_VAULTING'));
    }
}
