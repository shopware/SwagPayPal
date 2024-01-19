<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Test\PaymentsApi\Builder\OrderPaymentBuilderTest;

/**
 * @internal
 */
#[Package('checkout')]
class CurrencyRepoMock extends AbstractRepoMock
{
    public const INVALID_CURRENCY_ID = 'invalid-currency-id';

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        $currencyId = $criteria->getIds()[0];
        if (\is_array($currencyId)) {
            $currencyId = \implode('', $currencyId);
        }
        if ($currencyId === self::INVALID_CURRENCY_ID) {
            $currencyId = Uuid::randomHex();
        }
        $currency = new CurrencyEntity();
        $currency->setId($currencyId);
        $currency->setIsoCode(OrderPaymentBuilderTest::EXPECTED_ITEM_CURRENCY);

        /** @var EntitySearchResult $result */
        $result = new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            1,
            new CurrencyCollection([$currency]),
            null,
            $criteria,
            $context
        );

        return $result;
    }
}
