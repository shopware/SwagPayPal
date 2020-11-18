<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Route;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 *
 * @deprecated tag:v3.0.0 - Will be removed. Use ExpressPrepareCheckoutRoute instead
 */
class ExpressApprovePaymentRoute extends AbstractExpressApprovePaymentRoute
{
    /**
     * @deprecated tag:v3.0.0 - will be removed. Use ExpressPrepareCheckoutRoute::EXPRESS_CHECKOUT_ACTIVE instead
     */
    public const EXPRESS_CHECKOUT_ACTIVE = ExpressPrepareCheckoutRoute::EXPRESS_CHECKOUT_ACTIVE;

    /**
     * @var AbstractExpressPrepareCheckoutRoute
     */
    private $prepareCheckoutRoute;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(AbstractExpressPrepareCheckoutRoute $prepareCheckoutRoute, LoggerInterface $logger)
    {
        $this->prepareCheckoutRoute = $prepareCheckoutRoute;
        $this->logger = $logger;
    }

    public function getDecorated(): AbstractExpressApprovePaymentRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @deprecated tag:v3.0.0 - Will be removed. Use ExpressPrepareCheckoutRoute::prepareCheckout instead
     * @OA\Post(
     *     path="/paypal/approve-payment",
     *     description="Loggs in a guest customer, with the data of a paypal payment",
     *     operationId="approvePayPalExpressPayment",
     *     tags={"Store API", "PayPal"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="token", description="ID of the paypal order", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="The new context token"
     *    )
     * )
     *
     * @Route("/store-api/v{version}/paypal/approve-payment", name="store-api.paypal.approve_payment", methods={"POST"})
     */
    public function approve(SalesChannelContext $salesChannelContext, Request $request): ContextTokenResponse
    {
        $this->logger->error(
            \sprintf(
                'Route "store-api.paypal.approve_payment" is deprecated. Use "store-api.paypal.express.prepare_checkout" instead'
            )
        );

        return $this->prepareCheckoutRoute->prepareCheckout($salesChannelContext, $request);
    }
}
