<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SPBCheckout\SalesChannel\AbstractSPBCreateOrderRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated tag:v3.0.0 - Will be removed. Use SPBCreateOrderRoute instead
 */
class SPBCheckoutController extends AbstractController
{
    /**
     * @var AbstractSPBCreateOrderRoute
     */
    private $createOrderRoute;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(AbstractSPBCreateOrderRoute $createPaymentRoute, LoggerInterface $logger)
    {
        $this->createOrderRoute = $createPaymentRoute;
        $this->logger = $logger;
    }

    /**
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route(
     *     "/sales-channel-api/v{version}/_action/paypal/spb/create-payment",
     *      name="sales-channel-api.action.paypal.spb.create_payment",
     *      methods={"POST"}
     * )
     *
     * @throws CustomerNotLoggedInException
     */
    public function createPayment(SalesChannelContext $salesChannelContext, Request $request): JsonResponse
    {
        $this->logger->error(
            'Route "sales-channel-api.action.paypal.spb.create_payment" is deprecated. Use "store-api.paypal.spb.create_order" instead'
        );

        $response = $this->createOrderRoute->createPayPalOrder($salesChannelContext, $request);

        return new JsonResponse($response->getObject());
    }
}
