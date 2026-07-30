<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(ACDCMethodData::class)]
class ACDCMethodDataTest extends TestCase
{
    private ACDCMethodData $acdcMethodData;

    private MockObject&ContainerInterface $container;

    private MockObject&SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);

        $this->container
            ->method('get')
            ->with(SystemConfigService::class)
            ->willReturn($this->systemConfigService);

        $this->acdcMethodData = new ACDCMethodData($this->container);
    }

    public function testIsAvailableReturnsTrueForNonSubscription(): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'salesChannelId' => 'sales-channel-id',
            'subscription' => false,
        ]);

        $this->systemConfigService
            ->expects($this->never())
            ->method('getBool');

        static::assertTrue($this->acdcMethodData->isAvailable($availabilityContext));
    }

    public function testIsAvailableUsesVaultACDCSettingForSubscriptions(): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'salesChannelId' => 'sales-channel-id',
            'subscription' => true,
        ]);

        $this->systemConfigService
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::VAULTING_ENABLED_ACDC, 'sales-channel-id')
            ->willReturn(true);

        static::assertTrue($this->acdcMethodData->isAvailable($availabilityContext));
    }

    public function testIsAvailableReturnsFalseForSubscriptionsWithoutACDCVaulting(): void
    {
        $availabilityContext = new AvailabilityContext();
        $availabilityContext->assign([
            'salesChannelId' => 'sales-channel-id',
            'subscription' => true,
        ]);

        $this->systemConfigService
            ->expects($this->once())
            ->method('getBool')
            ->with(Settings::VAULTING_ENABLED_ACDC, 'sales-channel-id')
            ->willReturn(false);

        static::assertFalse($this->acdcMethodData->isAvailable($availabilityContext));
    }
}
