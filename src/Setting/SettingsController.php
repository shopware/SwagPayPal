<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Api\EventListener\ErrorResponseFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Setting\Service\SettingsSaverInterface;
use Swag\PayPal\Setting\Struct\MerchantInformationStruct;
use Swag\PayPal\Setting\Struct\SettingsInformationStruct;
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
        private readonly MerchantIntegrationsService $merchantIntegrationsService,
        private readonly SystemConfigValidator $systemConfigValidator,
        private readonly SettingsSaverInterface $settingsSaver,
    ) {
    }

    #[OA\Post(
        path: '/_action/paypal/test-api-credentials',
        operationId: 'testApiCredentials',
        tags: ['Admin Api', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns if the provided API credentials are valid',
            content: new OA\JsonContent(
                required: ['valid', 'errors'],
                properties: [
                    new OA\Property(
                        property: 'valid',
                        type: 'boolean',
                    ),
                    new OA\Property(
                        property: 'errors',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/error'),
                    ),
                ]
            )
        )]
    )]
    #[Route(path: '/api/_action/paypal/test-api-credentials', name: 'api.action.paypal.test-api-credentials', methods: ['POST'], defaults: ['_acl' => ['swag_paypal.viewer']])]
    public function testApiCredentials(RequestDataBag $data): JsonResponse
    {
        $clientId = $data->getString('clientId');
        if (!$clientId) {
            throw RoutingException::invalidRequestParameter('clientId');
        }

        $clientSecret = $data->getString('clientSecret');
        if (!$clientSecret) {
            throw RoutingException::invalidRequestParameter('clientSecret');
        }

        $merchantPayerId = $data->get('merchantPayerId');
        if ($merchantPayerId !== null && !\is_string($merchantPayerId)) {
            throw RoutingException::invalidRequestParameter('merchantPayerId');
        }

        $sandboxActive = $data->getBoolean('sandboxActive');

        try {
            $valid = $this->apiCredentialService->testApiCredentials($clientId, $clientSecret, $sandboxActive, $merchantPayerId);
        } catch (PayPalApiException $error) {
            $valid = false;
            $errors = (new ErrorResponseFactory())->getErrorsFromException($error);
        }

        return new JsonResponse([
            'valid' => $valid,
            'errors' => $errors ?? [],
        ]);
    }

    #[OA\Post(
        path: '/_action/paypal/get-api-credentials',
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

        return new JsonResponse([
            'client_id' => $credentials->getClientId(),
            'client_secret' => $credentials->getClientSecret(),
            'payer_id' => $credentials->getPayerId(),
        ]);
    }

    #[OA\Get(
        path: '/_action/paypal/merchant-information',
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

    #[OA\Post(
        path: '/_action/paypal/save-settings',
        operationId: 'saveSettings',
        tags: ['Admin Api', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns information about the saved settings',
            content: new OA\JsonContent(type: 'object', additionalProperties: new OA\AdditionalProperties(ref: SettingsInformationStruct::class))
        )]
    )]
    #[Route(path: '/api/_action/paypal/save-settings', name: 'api.action.paypal.settings.save', methods: ['POST'], defaults: ['_acl' => ['swag_paypal.editor', 'system_config:update', 'system_config:create', 'system_config:delete']])]
    public function saveSettings(RequestDataBag $data, Context $context): JsonResponse
    {
        $this->systemConfigValidator->validate($data->all(), $context);

        $information = [];

        /**
         * @var string $salesChannel
         * @var array<string, mixed> $kvs
         */
        foreach ($data->all() as $salesChannel => $kvs) {
            $information[$salesChannel] = $this->settingsSaver->save($kvs, $salesChannel === 'null' ? null : $salesChannel);
        }

        return new JsonResponse($information);
    }
}
