<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Swag\PayPal\Pos\Exception\ExistingPosAccountException;
use Swag\PayPal\Pos\Setting\Service\ApiCredentialService;
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\Pos\Setting\Service\InformationFetchService;
use Swag\PayPal\Pos\Setting\Service\ProductCountService;
use Swag\PayPal\Pos\Setting\Service\ProductVisibilityCloneService;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;
use Swag\PayPal\Pos\Setting\Struct\ProductCount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class SettingsController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ApiCredentialService $apiCredentialService,
        private readonly InformationFetchService $informationFetchService,
        private readonly InformationDefaultService $informationDefaultService,
        private readonly ProductVisibilityCloneService $productVisibilityCloneService,
        private readonly ProductCountService $productCountService,
    ) {
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/validate-api-credentials',
        operationId: 'posValidateApiCredentials',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(
                property: 'apiKey',
                type: 'string',
            ),
            new OA\Property(
                property: 'salesChannelId',
                type: 'string',
                pattern: '^[0-9a-f]{32}$',
            ),
        ])),
        tags: ['Admin Api', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Validation result of the API credentials',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'credentialsValid',
                type: 'boolean',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/pos/validate-api-credentials', name: 'api.action.paypal.pos.validate.api.credentials', methods: ['POST'], defaults: ['_acl' => ['sales_channel.editor']])]
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');
        if (!\is_string($apiKey)) {
            throw RoutingException::invalidRequestParameter('apiKey');
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

    #[OA\Post(
        path: '/api/paypal/pos/fetch-information',
        operationId: 'posFetchInformation',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(
                property: 'apiKey',
                type: 'string',
            ),
        ])),
        tags: ['Admin Api', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Fetched information',
            content: new OA\JsonContent(ref: AdditionalInformation::class),
        )]
    )]
    #[Route(path: '/api/paypal/pos/fetch-information', name: 'api.paypal.pos.fetch.information', methods: ['POST'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function fetchInformation(Request $request, Context $context): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');
        if (!\is_string($apiKey)) {
            throw RoutingException::invalidRequestParameter('apiKey');
        }

        $information = new AdditionalInformation();
        $this->informationFetchService->addInformation($information, $apiKey, $context);
        $this->informationDefaultService->addInformation($information, $context);

        return new JsonResponse($information);
    }

    #[OA\Post(
        path: '/api/_action/paypal/pos/clone-product-visibility',
        operationId: 'posCloneProductVisibility',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(
                property: 'fromSalesChannelId',
                type: 'string',
                pattern: '^[0-9a-f]{32}$',
            ),
            new OA\Property(
                property: 'toSalesChannelId',
                type: 'string',
                pattern: '^[0-9a-f]{32}$',
            ),
        ])),
        tags: ['Admin Api', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Cloning of product visibility was successful',
        )]
    )]
    #[Route(path: '/api/_action/paypal/pos/clone-product-visibility', name: 'api.action.paypal.pos.clone.product.visibility', methods: ['POST'], defaults: ['_acl' => ['sales_channel.editor']])]
    public function cloneProductVisibility(Request $request, Context $context): Response
    {
        $fromSalesChannelId = $request->request->getAlnum('fromSalesChannelId');
        $toSalesChannelId = $request->request->getAlnum('toSalesChannelId');

        $this->productVisibilityCloneService->cloneProductVisibility($fromSalesChannelId, $toSalesChannelId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(
        path: '/api/paypal/pos/product-count',
        operationId: 'posGetProductCounts',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'salesChannelId',
                name: 'salesChannelId',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'cloneSalesChannelId',
                name: 'cloneSalesChannelId',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Product counts',
            content: new OA\JsonContent(ref: ProductCount::class)
        )]
    )]
    #[Route(path: '/api/paypal/pos/product-count', name: 'api.paypal.pos.product.count', methods: ['GET'], defaults: ['_acl' => ['sales_channel.viewer']])]
    public function getProductCounts(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->getAlnum('salesChannelId');
        $cloneSalesChannelId = $request->query->getAlnum('cloneSalesChannelId');

        $productCounts = $this->productCountService->getProductCounts($salesChannelId, $cloneSalesChannelId, $context);

        return new JsonResponse($productCounts);
    }
}
