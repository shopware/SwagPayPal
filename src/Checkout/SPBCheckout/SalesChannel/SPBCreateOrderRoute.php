<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\RestApi\V2\Api\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated tag:v5.0.0 - will be removed, use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute instead
 *
 * @RouteScope(scopes={"store-api"})
 */
class SPBCreateOrderRoute extends AbstractSPBCreateOrderRoute
{
    private AbstractCreateOrderRoute $createOrderRoute;

    public function __construct(AbstractCreateOrderRoute $createOrderRoute)
    {
        $this->createOrderRoute = $createOrderRoute;
    }

    public function getDecorated(): AbstractSPBCreateOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("2.0.0")
     * @OA\Post(
     *     path="/store-api/paypal/spb/create-order",
     *     description="Creates a PayPal order from the existing cart",
     *     operationId="createPayPalSPBOrder",
     *     tags={"Store API", "PayPal"},
     *     @OA\Response(
     *         response="200",
     *         description="The new token of the order"
     *    )
     * )
     *
     * @Route(
     *     "/store-api/paypal/spb/create-order",
     *      name="store-api.paypal.spb.create_order",
     *      methods={"POST"}
     * )
     *
     * @throws CustomerNotLoggedInException
     */
    public function createPayPalOrder(SalesChannelContext $salesChannelContext, Request $request): TokenResponse
    {
        $request->request->set('product', 'spb');

        return $this->createOrderRoute->createPayPalOrder($salesChannelContext, $request);
    }
}
