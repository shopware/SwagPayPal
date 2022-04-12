<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractMethodEligibilityRoute
{
    abstract public function getDecorated(): AbstractErrorRoute;

    abstract public function setPaymentMethodEligibility(Request $request, Context $context): Response;
}
