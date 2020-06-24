<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\Lifecycle\Update;

class UpdateTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;

    private const CLIENT_ID = 'testClientId';
    private const CLIENT_SECRET = 'testClientSecret';
    private const OTHER_CLIENT_ID = 'someOtherTestClientId';
    private const OTHER_CLIENT_SECRET = 'someOtherTestClientSecret';

    public function testUpdateTo130WithNoPreviousSettings(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock();
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = new Update($systemConfigService);
        $update->update($updateContext);
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo130WithSandboxEnabled(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => self::CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => self::CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = new Update($systemConfigService);
        $update->update($updateContext);
        static::assertSame('', $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'));
        static::assertSame('', $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'));
        static::assertSame(self::CLIENT_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertSame(self::CLIENT_SECRET, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo130WithSandboxDisabled(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => self::CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => self::CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => false,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = new Update($systemConfigService);
        $update->update($updateContext);
        static::assertSame(self::CLIENT_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId'));
        static::assertSame(self::CLIENT_SECRET, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret'));
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertNull($systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    public function testUpdateTo130WithSandboxSettingsSet(): void
    {
        $systemConfigService = $this->createSystemConfigServiceMock([
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId' => self::CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret' => self::CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox' => self::OTHER_CLIENT_ID,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox' => self::OTHER_CLIENT_SECRET,
            SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox' => true,
        ]);
        $updateContext = $this->createUpdateContext('1.2.0', '1.3.0');
        $update = new Update($systemConfigService);
        $update->update($updateContext);
        static::assertSame(self::OTHER_CLIENT_ID, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox'));
        static::assertSame(self::OTHER_CLIENT_SECRET, $systemConfigService->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox'));
    }

    private function createUpdateContext(string $currentPluginVersion, string $nextPluginVersion): UpdateContext
    {
        /** @var MigrationCollectionLoader $migrationLoader */
        $migrationLoader = $this->getContainer()->get(MigrationCollectionLoader::class);

        return new UpdateContext(
            new SwagPayPal(true, ''),
            Context::createDefaultContext(),
            '',
            $currentPluginVersion,
            $migrationLoader->collect('core'),
            $nextPluginVersion
        );
    }
}
