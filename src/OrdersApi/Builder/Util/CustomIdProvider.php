<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Commercial\SwagCommercial;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\PluginCollection;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class CustomIdProvider
{
    protected ?string $pluginVersion = null;

    protected bool $commercialInstalled = false;

    /**
     * @internal
     *
     * @param EntityRepository<PluginCollection> $pluginRepository
     */
    public function __construct(
        private readonly EntityRepository $pluginRepository,
        protected string $shopwareVersion,
    ) {
    }

    public function createCustomId(
        OrderTransactionEntity $orderTransaction,
        Context $context,
    ): string {
        return \json_encode([
            'orderTransactionId' => $orderTransaction->getId(),
            'pluginVersion' => $this->getPluginVersion($context),
            'shopwareVersion' => $this->shopwareVersion,
        ], \JSON_THROW_ON_ERROR);
    }

    private function getPluginVersion(Context $context): string
    {
        if (!$this->pluginVersion) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('baseClass', [SwagPayPal::class, SwagCommercial::class]));
            $plugins = $this->pluginRepository->search($criteria, $context)->getEntities();

            foreach ($plugins as $plugin) {
                if ($plugin->getBaseClass() === SwagPayPal::class) {
                    $isValidVersion = \version_compare($plugin->getVersion() ?? '', '0.0.1', '>=');
                    $this->pluginVersion = $isValidVersion ? \mb_substr($plugin->getVersion(), 0, 6) : '0.0.0';
                }

                if ($plugin->getBaseClass() === SwagCommercial::class) {
                    $this->commercialInstalled = $plugin->getActive();
                }
            }

            $this->pluginVersion = $this->pluginVersion ?? '0.0.0';
        }

        return $this->pluginVersion . ($this->commercialInstalled ? '-c' : '');
    }
}
