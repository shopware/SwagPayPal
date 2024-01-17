<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;

/**
 * @internal
 */
#[Package('checkout')]
class ServiceDefinitionTest extends TestCase
{
    use KernelTestBehaviour;

    public function testEverythingIsInstantiatable(): void
    {
        $separateKernel = KernelLifecycleManager::createKernel(
            TestKernel::class,
            true,
            'h8f3f0ee9c61829627676afd6294bb029',
            $this->getKernel()->getProjectDir()
        );
        $separateKernel->boot();

        // @phpstan-ignore-next-line
        $testContainer = $separateKernel->getContainer()->get('test.service_container');

        static::assertInstanceOf(TestContainer::class, $testContainer);

        $errors = [];
        foreach ($testContainer->getServiceIds() as $serviceId) {
            try {
                $testContainer->get($serviceId);
            } catch (\Throwable $t) {
                $errors[] = $serviceId . ':' . $t->getMessage();
            }
        }

        static::assertCount(0, $errors, 'Found invalid services: ' . \print_r($errors, true));
    }
}
