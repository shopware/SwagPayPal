<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

/**
 * @internal
 */
#[Package('checkout')]
class ShopwarePluginClassTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testHasComposerJson(): void
    {
        static::assertFileExists(__DIR__ . '/../composer.json');
    }

    #[Depends('testHasComposerJson')]
    public function testClassExists(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);

        static::assertArrayHasKey('extra', $composer);
        static::assertArrayHasKey('shopware-plugin-class', $composer['extra']);

        $class = $composer['extra']['shopware-plugin-class'];
        static::assertTrue(class_exists($class), 'shopware-plugin-class `' . $class . '` does not exist');

        $parents = class_parents($class);
        static::assertNotFalse($parents);
        static::assertContains(Plugin::class, $parents, '`' . $class . '` should extend ' . Plugin::class);
    }

    #[Depends('testClassExists')]
    public function testPluginIsLoaded(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);
        $class = $composer['extra']['shopware-plugin-class'];

        static::assertNotNull($this->getContainer()->get($class));

        $pluginInfos = $this->getContainer()->getParameter('kernel.active_plugins');

        static::assertArrayHasKey($class, $pluginInfos);
    }
}
