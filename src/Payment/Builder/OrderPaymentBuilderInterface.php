<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Builder;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Payment\Exception\CurrencyNotFoundException;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

interface OrderPaymentBuilderInterface
{
    /**
     * Returns all necessary data to create a payment via the PayPal API. Uses data given by a Shopware order
     *
     * @throws CurrencyNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws InvalidOrderException
     * @throws PayPalSettingsInvalidException
     */
    public function getPayment(AsyncPaymentTransactionStruct $paymentTransaction, SalesChannelContext $salesChannelContext): Payment;
}
