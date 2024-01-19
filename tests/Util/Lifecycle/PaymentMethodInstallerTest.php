<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Util\Lifecycle\Installer\MediaInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentMethodInstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private EntityRepository $ruleRepository;

    private EntityRepository $paymentMethodRepository;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->getRepository(RuleDefinition::ENTITY_NAME);
        $this->paymentMethodRepository = $this->getRepository(PaymentMethodDefinition::ENTITY_NAME);
    }

    #[DataProvider('dataProviderContainerUse')]
    public function testUninstallDeletesRule(bool $useContainer): void
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class));
        $paymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        static::assertNotNull($paymentMethodId);

        $this->ruleRepository->upsert([[
            'name' => 'PayPalTestAvailabilityRule',
            'priority' => 1,
            'paymentMethods' => [
                ['id' => $paymentMethodId],
            ],
        ]], $context);

        $installer = $this->createInstaller($useContainer);
        $installer->removeRules($context);

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('name', 'PayPal'));
        $puiRuleId = $this->ruleRepository->searchIds($criteria, $context)->firstId();
        static::assertNull($puiRuleId);
    }

    #[DataProvider('dataProviderContainerUse')]
    public function testInstallAll(bool $useContainer): void
    {
        $context = Context::createDefaultContext();
        $installer = $this->createInstaller($useContainer);
        $installer->installAll($context);

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->search(new Criteria(), $context)->getEntities();
        $savedHandlers = \array_map(static function (PaymentMethodEntity $paymentMethod) {
            return $paymentMethod->getHandlerIdentifier();
        }, $paymentMethods->getElements());

        $registry = $this->getContainer()->get(PaymentHandlerRegistry::class);

        $reflectionRegistry = new \ReflectionObject($registry);
        $reflectionProperty = $reflectionRegistry->getProperty('handlers');
        $reflectionProperty->setAccessible(true); // <--- you set the property to public before you read the value

        /** @var array<string, SynchronousPaymentHandlerInterface|AsynchronousPaymentHandlerInterface> $registeredHandlers */
        $registeredHandlers = $reflectionProperty->getValue($registry);

        foreach (\array_keys($registeredHandlers) as $serviceId) {
            if (\mb_strpos($serviceId, '\\Swag\\PayPal') === false) {
                continue;
            }

            static::assertContains($serviceId, $savedHandlers);
        }

        foreach ($savedHandlers as $serviceId) {
            static::assertContains($serviceId, \array_keys($registeredHandlers));
        }
    }

    public static function dataProviderContainerUse(): iterable
    {
        return [
            'Installer from container' => [true],
            'Installer created manually' => [false],
        ];
    }

    private function createInstaller(bool $useContainer): PaymentMethodInstaller
    {
        if ($useContainer) {
            return $this->getContainer()->get(PaymentMethodInstaller::class);
        }

        return new PaymentMethodInstaller(
            $this->paymentMethodRepository,
            $this->ruleRepository,
            $this->getContainer()->get(PluginIdProvider::class),
            new PaymentMethodDataRegistry(
                $this->paymentMethodRepository,
                $this->getContainer(),
            ),
            new MediaInstaller(
                $this->getRepository(MediaDefinition::ENTITY_NAME),
                $this->getRepository(MediaFolderDefinition::ENTITY_NAME),
                $this->paymentMethodRepository,
                $this->getContainer()->get(FileSaver::class),
            ),
        );
    }

    private function getRepository(string $entityName): EntityRepository
    {
        $repository = $this->getContainer()->get(\sprintf('%s.repository', $entityName), ContainerInterface::NULL_ON_INVALID_REFERENCE);

        if (!$repository instanceof EntityRepository) {
            throw new ServiceNotFoundException(\sprintf('%s.repository', $entityName));
        }

        return $repository;
    }
}
