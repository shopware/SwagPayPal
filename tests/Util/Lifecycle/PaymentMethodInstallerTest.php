<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Util\Lifecycle\Installer\MediaInstaller;
use Swag\PayPal\Util\Lifecycle\Installer\PaymentMethodInstaller;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;

class PaymentMethodInstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private EntityRepositoryInterface $ruleRepository;

    private EntityRepositoryInterface $ruleConditionRepository;

    private EntityRepositoryInterface $paymentMethodRepository;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->getContainer()->get('rule.repository');
        $this->ruleRepository = $ruleRepository;
        /** @var EntityRepositoryInterface $ruleConditionRepository */
        $ruleConditionRepository = $this->getContainer()->get('rule_condition.repository');
        $this->ruleConditionRepository = $ruleConditionRepository;
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * @dataProvider dataProviderContainerUse
     */
    public function testUninstallDeletesRule(bool $useContainer): void
    {
        $context = Context::createDefaultContext();
        $installer = $this->createInstaller($useContainer);

        $ruleName = (new PUIMethodData($this->getContainer()))->getRuleData($context)['name'] ?? 'invalid';

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $ruleName));

        $puiRuleId = $this->ruleRepository->searchIds($criteria, $context)->firstId();
        static::assertNotNull($puiRuleId);

        $installer->removeRules($context);

        $puiRuleId = $this->ruleRepository->searchIds($criteria, $context)->firstId();
        static::assertNull($puiRuleId);
    }

    /**
     * @dataProvider dataProviderContainerUse
     */
    public function testInstallAll(bool $useContainer): void
    {
        $context = Context::createDefaultContext();
        $installer = $this->createInstaller($useContainer);
        $installer->installAll($context);

        $paymentMethods = $this->paymentMethodRepository->search(new Criteria(), $context);
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

    public function dataProviderContainerUse(): iterable
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

        /** @var EntityRepositoryInterface $mediaRepository */
        $mediaRepository = $this->getContainer()->get(MediaDefinition::ENTITY_NAME . '.repository');
        /** @var EntityRepositoryInterface $mediaFolderRepository */
        $mediaFolderRepository = $this->getContainer()->get(MediaFolderDefinition::ENTITY_NAME . '.repository');

        return new PaymentMethodInstaller(
            $this->paymentMethodRepository,
            $this->ruleRepository,
            $this->ruleConditionRepository,
            $this->getContainer()->get(PluginIdProvider::class),
            new PaymentMethodDataRegistry(
                $this->paymentMethodRepository,
                $this->getContainer(),
            ),
            new MediaInstaller(
                $mediaRepository,
                $mediaFolderRepository,
                $this->paymentMethodRepository,
                $this->getContainer()->get(FileSaver::class),
            ),
        );
    }
}
