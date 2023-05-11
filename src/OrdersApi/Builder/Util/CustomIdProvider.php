<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\Framework\Plugin\PluginService;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Handling;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping as BreakdownShipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\TaxTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\PriceFormatter;

class CustomIdProvider
{
    private EntityRepository $pluginRepository;

    protected string $shopwareVersion;

    protected ?string $pluginVersion = null;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $pluginRepository,
        string $shopwareVersion,
    ) {
        $this->pluginRepository = $pluginRepository;
        $this->shopwareVersion = $shopwareVersion;
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
            $criteria->addFilter(new EqualsFilter('baseClass', SwagPayPal::class));
            /** @var PluginEntity|null $plugin */
            $plugin = $this->pluginRepository->search($criteria, $context)->first();

            if ($plugin === null) {
                return $this->pluginVersion = '0.0.0';
            }

            $this->pluginVersion = $plugin->getVersion();
        }

        return $this->pluginVersion;
    }
}
