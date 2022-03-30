<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\SalesChannel;

use Shopware\Core\Checkout\Cart\Exception\OrderTransactionNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPUIPaymentInstructionsRoute
{
    abstract public function getDecorated(): AbstractPUIPaymentInstructionsRoute;

    /**
     * @throws OrderTransactionNotFoundException
     */
    abstract public function getPaymentInstructions(string $transactionId, SalesChannelContext $salesChannelContext): PUIPaymentInstructionsResponse;
}
