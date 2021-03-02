<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Swag\PayPal\Pos\Exception\ExistingPosAccountException;
use Swag\PayPal\Pos\Setting\Service\ApiCredentialService;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Setting\Service\InformationFetchService;
use Swag\PayPal\Pos\Setting\Service\ProductCountService;
use Swag\PayPal\Pos\Setting\Service\ProductVisibilityCloneService;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;
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

    /**
     * @var ProductCountService
     */
    private $productCountService;

    public function __construct(
        ApiCredentialService $apiService,
        InformationFetchService $informationFetchService,
        InformationDefaultService $informationDefaultService,
        ProductVisibilityCloneService $productVisibilityCloneService,
        ProductCountService $productCountService
    ) {
        $this->apiCredentialService = $apiService;
        $this->informationFetchService = $informationFetchService;
        $this->informationDefaultService = $informationDefaultService;
        $this->productVisibilityCloneService = $productVisibilityCloneService;
        $this->productCountService = $productCountService;
    }

    /**
     * @Route(
     *     "/api/_action/paypal/pos/validate-api-credentials",
     *     name="api.action.paypal.pos.validate.api.credentials",
     *     methods={"POST"}
     * )
     * @Acl({"sales_channel.editor"})
     */
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');
        if ($apiKey === null) {
            throw new MissingRequestParameterException('apiKey');
        }

        $salesChannelId = $request->request->getAlnum('salesChannelId');

        $credentialsValid = $this->apiCredentialService->testApiCredentials($apiKey);
        $duplicates = $this->apiCredentialService->checkForDuplicates($apiKey, $context);
        if (\count($duplicates) > 0
            && ($salesChannelId === '' || \count($duplicates) > 1 || !isset($duplicates[$salesChannelId]))
        ) {
            throw new ExistingPosAccountException($duplicates);
        }

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }

    /**
     * @Route(
     *     "/api/paypal/pos/fetch-information",
     *     name="api.paypal.pos.fetch.information",
     *     methods={"POST"}
     * )
     * @Acl({"sales_channel.viewer"})
     */
    public function fetchInformation(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');
        if ($apiKey === null) {
            throw new MissingRequestParameterException('apiKey');
        }

        $information = new AdditionalInformation();
        $this->informationFetchService->addInformation($information, $apiKey, $context);
        $this->informationDefaultService->addInformation($information, $context);

        return new JsonResponse($information);
    }

    /**
     * @Route(
     *     "/api/_action/paypal/pos/clone-product-visibility",
     *     name="api.action.paypal.pos.clone.product.visibility",
     *     methods={"POST"}
     * )
     * @Acl({"sales_channel.editor"})
     */
    public function cloneProductVisibility(Request $request, Context $context): Response
    {
        $fromSalesChannelId = $request->request->getAlnum('fromSalesChannelId');
        $toSalesChannelId = $request->request->getAlnum('toSalesChannelId');

        $this->productVisibilityCloneService->cloneProductVisibility($fromSalesChannelId, $toSalesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(
     *     "/api/paypal/pos/product-count",
     *     name="api.paypal.pos.product.count",
     *     methods={"GET"}
     * )
     * @Acl({"sales_channel.viewer"})
     */
    public function getProductCounts(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->getAlnum('salesChannelId');
        $cloneSalesChannelId = $request->query->getAlnum('cloneSalesChannelId');

        $productCounts = $this->productCountService->getProductCounts($salesChannelId, $cloneSalesChannelId, $context);

        return new JsonResponse($productCounts);
    }
}
