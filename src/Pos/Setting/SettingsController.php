<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
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
 * @Route(defaults={"_routeScope"={"api"}})
 */
class SettingsController extends AbstractController
{
    private ApiCredentialService $apiCredentialService;

    private InformationFetchService $informationFetchService;

    private InformationDefaultService $informationDefaultService;

    private ProductVisibilityCloneService $productVisibilityCloneService;

    private ProductCountService $productCountService;

    /**
     * @internal
     */
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
     * @Since("1.9.0")
     *
     * @Route(
     *     "/api/_action/paypal/pos/validate-api-credentials",
     *     name="api.action.paypal.pos.validate.api.credentials",
     *     methods={"POST"},
     *     defaults={"_acl": {"sales_channel.editor"}}
     * )
     */
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');
        if (!\is_string($apiKey)) {
            throw new InvalidRequestParameterException('apiKey');
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
     * @Since("1.9.0")
     *
     * @Route(
     *     "/api/paypal/pos/fetch-information",
     *     name="api.paypal.pos.fetch.information",
     *     methods={"POST"},
     *     defaults={"_acl": {"sales_channel.viewer"}}
     * )
     */
    public function fetchInformation(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');
        if (!\is_string($apiKey)) {
            throw new InvalidRequestParameterException('apiKey');
        }

        $information = new AdditionalInformation();
        $this->informationFetchService->addInformation($information, $apiKey, $context);
        $this->informationDefaultService->addInformation($information, $context);

        return new JsonResponse($information);
    }

    /**
     * @Since("1.9.0")
     *
     * @Route(
     *     "/api/_action/paypal/pos/clone-product-visibility",
     *     name="api.action.paypal.pos.clone.product.visibility",
     *     methods={"POST"},
     *     defaults={"_acl": {"sales_channel.editor"}}
     * )
     */
    public function cloneProductVisibility(Request $request, Context $context): Response
    {
        $fromSalesChannelId = $request->request->getAlnum('fromSalesChannelId');
        $toSalesChannelId = $request->request->getAlnum('toSalesChannelId');

        $this->productVisibilityCloneService->cloneProductVisibility($fromSalesChannelId, $toSalesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Since("1.9.0")
     *
     * @Route(
     *     "/api/paypal/pos/product-count",
     *     name="api.paypal.pos.product.count",
     *     methods={"GET"},
     *     defaults={"_acl": {"sales_channel.viewer"}}
     * )
     */
    public function getProductCounts(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->getAlnum('salesChannelId');
        $cloneSalesChannelId = $request->query->getAlnum('cloneSalesChannelId');

        $productCounts = $this->productCountService->getProductCounts($salesChannelId, $cloneSalesChannelId, $context);

        return new JsonResponse($productCounts);
    }
}
