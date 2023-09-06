<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CategoryRouteResponse;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCheckoutDataServiceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
#[Package('checkout')]
class ExpressCategoryRoute extends AbstractCategoryRoute
{
    private AbstractCategoryRoute $inner;

    private ExpressCheckoutDataServiceInterface $expressCheckoutDataService;

    private SettingsValidationServiceInterface $settingsValidationService;

    private SystemConfigService $systemConfigService;

    private PaymentMethodUtil $paymentMethodUtil;

    /**
     * @internal
     */
    public function __construct(
        AbstractCategoryRoute $inner,
        ExpressCheckoutDataServiceInterface $expressCheckoutDataService,
        SettingsValidationServiceInterface $settingsValidationService,
        SystemConfigService $systemConfigService,
        PaymentMethodUtil $paymentMethodUtil
    ) {
        $this->inner = $inner;
        $this->expressCheckoutDataService = $expressCheckoutDataService;
        $this->settingsValidationService = $settingsValidationService;
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        return $this->inner;
    }

    /**
     * @Since("3.3.0")
     *
     * @OA\Post(
     *     path="/category/{categoryId}",
     *     summary="Fetch a single category",
     *     description="This endpoint returns information about the category, as well as a fully resolved (hydrated with mapping values) CMS page, if one is assigned to the category. You can pass slots which should be resolved exclusively.",
     *     operationId="readCategory",
     *     tags={"Store API", "Category"},
     *
     *     @OA\Parameter(
     *         name="categoryId",
     *         description="Identifier of the category to be fetched",
     *
     *         @OA\Schema(type="string", pattern="^[0-9a-f]{32}$"),
     *         in="path",
     *         required=true
     *     ),
     *
     *     @OA\Parameter(
     *         name="slots",
     *         description="Resolves only the given slot identifiers. The identifiers have to be seperated by a '|' character",
     *
     *         @OA\Schema(type="string"),
     *         in="query",
     *     ),
     *
     *     @OA\Parameter(name="Api-Basic-Parameters"),
     *
     *     @OA\Response(
     *          response="200",
     *          description="The loaded category with cms page",
     *
     *          @OA\JsonContent(ref="#/components/schemas/category_flat")
     *     )
     * )
     *
     * @Route("/store-api/category/{navigationId}", name="store-api.category.detail", methods={"GET","POST"})
     */
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
