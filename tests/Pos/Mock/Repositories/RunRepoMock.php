<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;

/**
 * @internal
 *
 * @extends AbstractRepoMock<PosSalesChannelRunCollection>
 */
#[Package('checkout')]
class RunRepoMock extends AbstractRepoMock
{
    public function getDefinition(): EntityDefinition
    {
        return new PosSalesChannelRunDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
        $sum = 0;
        foreach ($this->search($criteria, $context)->getEntities() as $entity) {
            $sum += $entity->getMessageCount();
        }

        return new AggregationResultCollection([
            new SumResult('totalMessages', $sum),
        ]);
    }

    public function getFirstRun(): ?PosSalesChannelRunEntity
    {
        $run = $this->entityCollection->first();

        return $run;
    }
}
