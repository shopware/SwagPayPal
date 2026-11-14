<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\JWT\Struct\JWKCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\Routing\RouterInterface;

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

    public function testEveryRouteControllerCanBeResolved(): void
    {
        $router = static::getContainer()->get('router');
        static::assertInstanceOf(RouterInterface::class, $router);

        // requests resolve their controller from the kernel's container, which only offers public services, unlike the test container above
        $controllerResolver = new ContainerControllerResolver(static::getKernel()->getContainer());

        // the agentic commerce routes are always imported, but their services are only registered with the JWT support of the core (see services.php)
        $hasAgenticCommerce = \class_exists(JWKCollection::class);

        $checked = 0;
        $errors = [];
        foreach ($router->getRouteCollection() as $routeName => $route) {
            $controller = $route->getDefault('_controller');
            if (!\is_string($controller) || !\str_starts_with($controller, 'Swag\\PayPal\\')) {
                continue;
            }

            if (!$hasAgenticCommerce && \str_starts_with($controller, 'Swag\\PayPal\\AgenticCommerce\\')) {
                continue;
            }

            ++$checked;
            $request = new Request();
            $request->attributes->set('_controller', $controller);

            try {
                if ($controllerResolver->getController($request) === false) {
                    $errors[] = $routeName . ': no controller found';
                }
            } catch (\Throwable $t) {
                $errors[] = $routeName . ': ' . $t->getMessage();
            }
        }

        static::assertGreaterThan(0, $checked);
        static::assertCount(0, $errors, 'Found routes whose controller cannot be resolved: ' . \print_r($errors, true));
    }
}
