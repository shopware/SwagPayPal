<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Swag\PayPal\IZettle\Setting\Service\ApiCredentialService;
use Swag\PayPal\IZettle\Setting\Service\InformationDefaultService;
use Swag\PayPal\IZettle\Setting\Service\InformationFetchService;
use Swag\PayPal\IZettle\Setting\Service\ProductVisibilityCloneService;
use Swag\PayPal\IZettle\Setting\Struct\AdditionalInformation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class SettingsController extends AbstractController
{
    /**
     * @var ApiCredentialService
     */
    private $apiCredentialService;

    /**
     * @var InformationFetchService
     */
    private $informationFetchService;

    /**
     * @var InformationDefaultService
     */
    private $informationDefaultService;

    /**
     * @var ProductVisibilityCloneService
     */
    private $productVisibilityCloneService;

    public function __construct(
        ApiCredentialService $apiService,
        InformationFetchService $informationFetchService,
        InformationDefaultService $informationDefaultService,
        ProductVisibilityCloneService $productVisibilityCloneService
    ) {
        $this->apiCredentialService = $apiService;
        $this->informationFetchService = $informationFetchService;
        $this->informationDefaultService = $informationDefaultService;
        $this->productVisibilityCloneService = $productVisibilityCloneService;
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/paypal/izettle/validate-api-credentials",
     *     name="api.action.paypal.izettle.validate.api.credentials",
     *     methods={"POST"}
     * )
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');

        $credentialsValid = $this->apiCredentialService->testApiCredentials($apiKey);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/paypal/izettle/fetch-information",
     *     name="api.action.paypal.izettle.fetch.information",
     *     methods={"POST"}
     * )
     */
    public function fetchInformation(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');

        $information = new AdditionalInformation();
        $this->informationFetchService->addInformation($information, $apiKey, $context);
        $this->informationDefaultService->addInformation($information, $context);

        return new JsonResponse($information);
    }

    /**
     * @Route(
     *     "/api/v{version}/_action/paypal/izettle/clone-product-visibility",
     *     name="api.action.paypal.izettle.clone.product.visibility",
     *     methods={"POST"}
     * )
     */
    public function cloneProductVisibility(Request $request, Context $context): Response
    {
        $fromSalesChannelId = $request->request->get('fromSalesChannelId');
        $toSalesChannelId = $request->request->get('toSalesChannelId');

        $this->productVisibilityCloneService->cloneProductVisibility($fromSalesChannelId, $toSalesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
