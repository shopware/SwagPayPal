<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V2\Order\ApplicationContext;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\OrderUpdateCallbackConfig;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Exception\OrderZeroValueException;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ExpressCreateOrderRoute extends AbstractExpressCreateOrderRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly PayPalOrderBuilder $paypalOrderBuilder,
        private readonly OrderResource $orderResource,
        private readonly CartPriceService $cartPriceService,
        private readonly SystemConfigService $systemConfigService,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getDecorated(): AbstractExpressCreateOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/paypal/express/create-order',
        operationId: 'createPayPalExpressOrder',
        description: 'Creates a PayPal order from the existing cart',
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The new token of the order'
        )]
    )]
    #[Route(path: '/store-api/paypal/express/create-order', name: 'store-api.paypal.express.create_order', methods: ['POST'])]
    public function createPayPalOrder(Request $request, SalesChannelContext $salesChannelContext): TokenResponse
    {
        try {
            $this->logger->debug('Started');
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

            if ($this->cartPriceService->isZeroValueCart($cart)) {
                throw new OrderZeroValueException();
            }

            $this->logger->debug('Building order');
            $order = $this->paypalOrderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag($request->request->all()));
            $experienceContext = $order->getPaymentSource()?->getPaypal()?->getExperienceContext();

            if ($experienceContext !== null) {
                $experienceContext->setShippingPreference(ApplicationContext::SHIPPING_PREFERENCE_GET_FROM_FILE);
                $experienceContext->setUserAction(ApplicationContext::USER_ACTION_CONTINUE);

                // Configure shipping callback for dynamic price recalculation
                if (!$this->systemConfigService->getBool(Settings::IS_LOCAL_ENVIRONMENT, $salesChannelContext->getSalesChannelId())) {
                    $callbackConfig = new OrderUpdateCallbackConfig();
                    $callbackUrl = $this->router->generate(
                        'store-api.paypal.express.shipping_callback',
                        ['salesChannelId' => $salesChannelContext->getSalesChannelId(), 'token' => $salesChannelContext->getToken()],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    );
                    $callbackConfig->setCallbackUrl($callbackUrl);
                    $callbackConfig->setCallbackEvents([OrderUpdateCallbackConfig::CALLBACK_EVENT_SHIPPING_OPTIONS]);
                    $experienceContext->setOrderUpdateCallbackConfig($callbackConfig);

                    $this->logger->debug('Configured shipping callback', ['callbackUrl' => $callbackUrl]);
                } else {
                    $this->logger->debug('Skipped shipping callback due to being disabled in system config');
                }
            }

            $orderResponse = $this->orderResource->create(
                $order,
                $salesChannelContext->getSalesChannel()->getId(),
                PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT
            );

            return new TokenResponse($orderResponse->getId());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw $e;
        }
    }
}
