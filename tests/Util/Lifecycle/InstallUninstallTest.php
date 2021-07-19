<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\Repositories\EntityRepositoryMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SystemConfigRepoMock;
use Swag\PayPal\Util\Lifecycle\InstallUninstall;

class InstallUninstallTest extends TestCase
{
    use DatabaseTransactionBehaviour;

    use KernelTestBehaviour;

    private EntityRepositoryInterface $ruleRepository;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->getContainer()->get('rule.repository');
        $this->ruleRepository = $ruleRepository;
    }

    public function testUninstallDeletesRule(): void
    {
        $context = Context::createDefaultContext();
        $installUninstall = $this->createInstallUninstall();
        $removeRuleMethod = (new \ReflectionClass($installUninstall))->getMethod('removePuiAvailabilityRule');
        $removeRuleMethod->setAccessible(true);

        $removeRuleMethod->invoke($installUninstall, $context);

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('name', InstallUninstall::PAYPAL_PUI_AVAILABILITY_RULE_NAME));

        $paypalPuiRule = $this->ruleRepository->search($criteria, $context)->first();
        static::assertNull($paypalPuiRule);
    }

    private function createInstallUninstall(): InstallUninstall
    {
        $container = $this->getContainer();
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $container->get('payment_method.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $container->get(PluginIdProvider::class);
        /** @var SystemConfigService $systemConfigService */
        $systemConfigService = $container->get(SystemConfigService::class);
        /** @var Connection $connection */
        $connection = $container->get(Connection::class);

        return new InstallUninstall(
            new SystemConfigRepoMock(),
            $paymentRepository,
            new SalesChannelRepoMock(),
            $this->ruleRepository,
            new EntityRepositoryMock(),
            $pluginIdProvider,
            $systemConfigService,
            $connection,
            SwagPayPal::class
        );
    }
}
