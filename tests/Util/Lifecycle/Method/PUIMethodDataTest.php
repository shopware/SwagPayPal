<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Product;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(PUIMethodData::class)]
class PUIMethodDataTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('provideValidateCapability')]
    public function testValidateCapability(array $data, string $expected): void
    {
        $merchantIntegrations = (new MerchantIntegrations())->assign($data);

        $methodData = new PUIMethodData($this->createMock(ContainerInterface::class));

        static::assertSame($expected, $methodData->validateCapability($merchantIntegrations));
    }

    public static function provideValidateCapability(): \Generator
    {
        $activeCapability = ['capabilities' => [['name' => 'PAY_UPON_INVOICE', 'status' => Capability::STATUS_ACTIVE]]];

        yield 'approved' => [
            ['products' => [['name' => 'PAYMENT_METHODS', 'vetting_status' => Product::VETTING_STATUS_APPROVED]], ...$activeCapability],
            AbstractMethodData::CAPABILITY_ACTIVE,
        ];
        yield 'subscribed' => [
            ['products' => [['name' => 'PAYMENT_METHODS', 'vetting_status' => Product::VETTING_STATUS_SUBSCRIBED]], ...$activeCapability],
            AbstractMethodData::CAPABILITY_ACTIVE,
        ];
        yield 'subscribed without capability' => [
            ['products' => [['name' => 'PAYMENT_METHODS', 'vetting_status' => Product::VETTING_STATUS_SUBSCRIBED]]],
            AbstractMethodData::CAPABILITY_INELIGIBLE,
        ];
        yield 'denied' => [
            ['products' => [['name' => 'PAYMENT_METHODS', 'vetting_status' => 'DENIED']], ...$activeCapability],
            AbstractMethodData::CAPABILITY_INELIGIBLE,
        ];
        yield 'missing vetting status' => [
            ['products' => [['name' => 'PAYMENT_METHODS']], ...$activeCapability],
            AbstractMethodData::CAPABILITY_INELIGIBLE,
        ];
        yield 'missing product' => [
            ['products' => [['name' => 'PPCP_CUSTOM', 'vetting_status' => Product::VETTING_STATUS_SUBSCRIBED]], ...$activeCapability],
            AbstractMethodData::CAPABILITY_INELIGIBLE,
        ];
        yield 'missing products' => [
            $activeCapability,
            AbstractMethodData::CAPABILITY_INELIGIBLE,
        ];
    }
}
