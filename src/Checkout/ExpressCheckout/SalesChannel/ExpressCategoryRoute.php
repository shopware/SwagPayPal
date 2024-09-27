<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Attributes as OA;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CategoryRouteResponse;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCheckoutDataServiceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ExpressCategoryRoute extends AbstractCategoryRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractCategoryRoute $inner,
        private readonly ExpressCheckoutDataServiceInterface $expressCheckoutDataService,
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly SystemConfigService $systemConfigService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
    ) {
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        return $this->inner;
    }

    #[OA\Post(
        path: '/store-api/category/{navigationId}',
        operationId: 'readCategory',
        description: 'This endpoint returns information about the category, as well as a fully resolved (hydrated with mapping values) CMS page, if one is assigned to the category. You can pass slots which should be resolved exclusively.',
        tags: ['Store API', 'Category'],
        parameters: [
            new OA\Parameter(
                name: 'navigationId',
                description: 'Identifier of the navigation to be fetched',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            ref: '#/components/schemas/category_flat',
            response: Response::HTTP_OK,
            description: 'The loaded category with cms page'
        )]
    )]
    #[Route(path: '/store-api/category/{navigationId}', name: 'store-api.category.detail', methods: ['GET', 'POST'])]
    public function load(string $navigationId, Request $request, SalesChannelContext $context): CategoryRouteResponse
    {
        $response = $this->inner->load($navigationId, $request, $context);

        $route = $request->attributes->get('_route');

        if (!\is_string($route) || empty($route)) {
            return $response;
        }

        if ($route !== 'frontend.cms.navigation.page') {
            return $response;
        }

        $cmsPage = $response->getCategory()->getCmsPage();
        if ($cmsPage === null) {
            return $response;
        }

        $settings = $this->checkSettings($context);
        if ($settings === false) {
            return $response;
        }

        $expressCheckoutButtonData = $this->expressCheckoutDataService->buildExpressCheckoutButtonData($context, true);
        if ($expressCheckoutButtonData === null) {
            return $response;
        }

        $cmsPage->addExtension(
            ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );

        return $response;
    }

    private function checkSettings(SalesChannelContext $context): bool
    {
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($context) === false) {
            return false;
        }

        try {
            $this->settingsValidationService->validate($context->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        if ($this->systemConfigService->getBool(Settings::ECS_LISTING_ENABLED, $context->getSalesChannelId()) === false) {
            return false;
        }

        return true;
    }
}
