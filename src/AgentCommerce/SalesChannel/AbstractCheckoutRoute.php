<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\AgentCartResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractCheckoutRoute
{
    abstract public function getDecorated(): AbstractCheckoutRoute;

    abstract public function checkout(string $token, Request $request, Context $context): AgentCartResponse;
}
