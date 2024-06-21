<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;

/**
 * @internal
 *
 * @extends AbstractRepoMock<PaymentMethodCollection>
 */
#[Package('checkout')]
class PaymentMethodRepoMock extends AbstractRepoMock
{
    public const PAYPAL_PAYMENT_METHOD_ID = '0afca95b4937428a884830cd516fb826';
    public const VERSION_ID_WITHOUT_PAYMENT_METHOD = 'WITHOUT_PAYMENT_METHOD';

    public function getDefinition(): EntityDefinition
    {
        return new PaymentMethodDefinition();
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        if ($context->getVersionId() === self::VERSION_ID_WITHOUT_PAYMENT_METHOD) {
            return $this->getIdSearchResult(false, $criteria, $context);
        }

        $firstFilter = $criteria->getFilters()[0];
        \assert($firstFilter instanceof EqualsFilter);
        if ($firstFilter->getValue() === PayPalPaymentHandler::class) {
            return $this->getIdSearchResult(true, $criteria, $context);
        }

        return $this->getIdSearchResult(false, $criteria, $context);
    }

    private function getIdSearchResult(bool $handlerFound, Criteria $criteria, Context $context): IdSearchResult
    {
        if ($handlerFound) {
            return new IdSearchResult(
                1,
                [
                    [
                        'primaryKey' => self::PAYPAL_PAYMENT_METHOD_ID,
                        'data' => [
                            'id' => self::PAYPAL_PAYMENT_METHOD_ID,
                        ],
                    ],
                ],
                $criteria,
                $context
            );
        }

        return new IdSearchResult(
            0,
            [],
            $criteria,
            $context
        );
    }
}
