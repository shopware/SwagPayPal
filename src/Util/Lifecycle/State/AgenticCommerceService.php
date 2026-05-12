<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\State;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Swag\PayPal\AgenticCommerce\Exception\HoneyWebhookException;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
class AgenticCommerceService
{
    /**
     * @internal
     *
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<SalesChannelTypeCollection> $salesChannelTypeRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $salesChannelTypeRepository,
    ) {
    }

    public function addAgenticSalesChannelType(Context $context): void
    {
        $this->salesChannelTypeRepository->upsert([
            [
                'id' => SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE,
                'coverUrl' => null,
                'iconName' => 'regular-artificial-intelligence',
                'screenshotUrls' => null,
                'name' => 'PayPal Agentic Commerce',
                'manufacturer' => 'shopware AG',
                'description' => 'PayPal Agentic Commerce Sales Channel',
                'descriptionLong' => 'The PayPal Agentic Commerce is an AI solution that allows customers to purchase products in a chat with an AI agent. Assign products that the agent should sell to enhance the shopping experience.',
                'translations' => [
                    'de-DE' => [
                        'name' => 'PayPal Agentic Commerce',
                        'manufacturer' => 'shopware AG',
                        'description' => 'PayPal Agentic Commerce Sales Channel',
                        'descriptionLong' => 'Der PayPal Agentic Commerce ist eine KI Lösung, die es Kunden ermöglicht, Produkte im Chat mit einem KI Agenten zu kaufen. Ordne Produkte zu, die der Agent verkaufen soll, um das Einkaufserlebnis zu verbessern.',
                    ],
                    'en-GB' => [
                        'name' => 'PayPal Agentic Commerce',
                        'manufacturer' => 'shopware AG',
                        'description' => 'PayPal Agentic Commerce Sales Channel',
                        'descriptionLong' => 'The PayPal Agentic Commerce is an AI solution that allows customers to purchase products in a chat with an AI agent. Assign products that the agent should sell to enhance the shopping experience.',
                    ],
                ],
            ],
        ], $context);
    }

    public function handleUninstallAgentic(Context $context): void
    {
        $ids = $this->checkAgenticSalesChannels($context);
        if ($ids === []) {
            $this->salesChannelTypeRepository->delete([['id' => SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE]], $context);
        }
    }

    public function deactivateAgenticSalesChannelState(Context $context): void
    {
        $ids = $this->checkAgenticSalesChannels($context);
        if ($ids === []) {
            return;
        }

        $updateData = \array_values(\array_map(
            static fn (string $id) => ['id' => $id, 'active' => false],
            $ids,
        ));

        try {
            $this->salesChannelRepository->update($updateData, $context);
        } catch (HoneyWebhookException $e) {
            if ($e->is(HoneyWebhookException::NOT_REGISTERED)) {
                return;
            }

            throw $e;
        }
    }

    /**
     * @return list<string>
     */
    private function checkAgenticSalesChannels(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE));

        /** @var list<string> $ids */
        $ids = $this->salesChannelRepository->searchIds($criteria, $context)->getIds();

        return $ids;
    }
}
