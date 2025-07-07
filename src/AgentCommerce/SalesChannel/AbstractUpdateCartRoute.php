<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\SalesChannel\Response\PayPalAgentCartResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractUpdateCartRoute
{
    abstract public function getDecorated(): AbstractUpdateCartRoute;

    abstract public function updateCart(string $token, Request $request, Context $context): PayPalAgentCartResponse;
}
