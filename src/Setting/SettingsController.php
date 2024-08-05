<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Setting\Service\ApiCredentialServiceInterface;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Setting\Struct\MerchantInformationStruct;
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
        private readonly ApiCredentialServiceInterface $apiCredentialService,
        private readonly MerchantIntegrationsService $merchantIntegrationsService,
    ) {
    }

    #[OA\Get(
        path: '/api/_action/paypal/validate-api-credentials',
        operationId: 'validateApiCredentials',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                name: 'clientId',
                description: 'The client id of the PayPal API credentials',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'clientSecret',
                description: 'The client secret of the PayPal API credentials',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sandboxActive',
                description: 'If the sandbox environment should be used',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', nullable: true)
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns if the provided API credentials are valid',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'credentialsValid',
                type: 'boolean',
            )])
        )]
    )]
    #[Route(path: '/api/_action/paypal/validate-api-credentials', name: 'api.action.paypal.validate.api.credentials', methods: ['GET'], defaults: ['_acl' => ['swag_paypal.viewer']])]
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $clientId = $request->query->get('clientId');
        if (!\is_string($clientId)) {
            throw RoutingException::invalidRequestParameter('clientId');
        }

        $clientSecret = $request->query->get('clientSecret');
        if (!\is_string($clientSecret)) {
            throw RoutingException::invalidRequestParameter('clientSecret');
        }

        $merchantPayerId = $request->query->get('merchantPayerId');
        if ($merchantPayerId !== null && !\is_string($merchantPayerId)) {
            throw RoutingException::invalidRequestParameter('merchantPayerId');
        }

        $sandboxActive = $request->query->getBoolean('sandboxActive');

        /* @phpstan-ignore-next-line method will have additional method */
        $credentialsValid = $this->apiCredentialService->testApiCredentials($clientId, $clientSecret, $sandboxActive, $merchantPayerId);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }

    #[OA\Post(
        path: '/api/_action/paypal/get-api-credentials',
        operationId: 'getApiCredentials',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'authCode', type: 'string'),
            new OA\Property(property: 'sharedId', type: 'string'),
            new OA\Property(property: 'nonce', type: 'string'),
            new OA\Property(property: 'sandboxActive', type: 'boolean'),
        ])),
        tags: ['Admin Api', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns the API credentials',
            content: new OA\JsonContent(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))
        )]
    )]
    #[Route(path: '/api/_action/paypal/get-api-credentials', name: 'api.action.paypal.get.api.credentials', methods: ['POST'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function getApiCredentials(RequestDataBag $requestDataBag): JsonResponse
    {
        $authCode = $requestDataBag->get('authCode');
        $sharedId = $requestDataBag->get('sharedId');
        $nonce = $requestDataBag->get('nonce');
        $sandboxActive = $requestDataBag->getBoolean('sandboxActive');

        $credentials = $this->apiCredentialService->getApiCredentials($authCode, $sharedId, $nonce, $sandboxActive);

        return new JsonResponse($credentials);
    }

    #[OA\Get(
        path: '/api/_action/paypal/merchant-information',
        operationId: 'getMerchantInformation',
        tags: ['Admin Api', 'PayPal'],
        parameters: [
            new OA\Parameter(
                name: 'salesChannelId',
                description: 'The id of the sales channel to get merchant information for',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', nullable: true),
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns information about the merchant',
            content: new OA\JsonContent(ref: MerchantInformationStruct::class)
        )]
    )]
    #[Route(path: '/api/_action/paypal/merchant-information', name: 'api.action.paypal.merchant-information', methods: ['GET'], defaults: ['_acl' => ['swag_paypal.editor']])]
    public function getMerchantInformation(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->getAlnum('salesChannelId') ?: null;

        $response = $this->merchantIntegrationsService->getMerchantInformation($context, $salesChannelId);

        return new JsonResponse($response);
    }
}
