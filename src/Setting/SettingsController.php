<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Setting\Service\ApiCredentialServiceInterface;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class SettingsController extends AbstractController
{
    private ApiCredentialServiceInterface $apiCredentialService;

    private MerchantIntegrationsService $merchantIntegrationsService;

    /**
     * @internal
     */
    public function __construct(
        ApiCredentialServiceInterface $apiService,
        MerchantIntegrationsService $merchantIntegrationsService
    ) {
        $this->apiCredentialService = $apiService;
        $this->merchantIntegrationsService = $merchantIntegrationsService;
    }

    /**
     * @Since("0.9.0")
     *
     * @Route("/api/_action/paypal/validate-api-credentials", name="api.action.paypal.validate.api.credentials", methods={"GET"}, defaults={"_acl": {"swag_paypal.viewer"}})
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $clientId = $request->query->get('clientId');
        if (!\is_string($clientId)) {
            throw new InvalidRequestParameterException('clientId');
        }

        $clientSecret = $request->query->get('clientSecret');
        if (!\is_string($clientSecret)) {
            throw new InvalidRequestParameterException('clientSecret');
        }

        $sandboxActive = $request->query->getBoolean('sandboxActive');

        $credentialsValid = $this->apiCredentialService->testApiCredentials($clientId, $clientSecret, $sandboxActive);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }

    /**
     * @Since("0.10.0")
     *
     * @Route("/api/_action/paypal/get-api-credentials", name="api.action.paypal.get.api.credentials", methods={"POST"}, defaults={"_acl": {"swag_paypal.editor"}})
     */
    public function getApiCredentials(RequestDataBag $requestDataBag): JsonResponse
    {
        $authCode = $requestDataBag->get('authCode');
        $sharedId = $requestDataBag->get('sharedId');
        $nonce = $requestDataBag->get('nonce');
        $sandboxActive = $requestDataBag->getBoolean('sandboxActive');

        $credentials = $this->apiCredentialService->getApiCredentials($authCode, $sharedId, $nonce, $sandboxActive);

        return new JsonResponse($credentials);
    }

    /**
     * @Since("5.2.0")
     *
     * @Route("/api/_action/paypal/merchant-information", name="api.action.paypal.merchant-information", methods={"GET"}, defaults={"_acl": {"swag_paypal.editor"}})
     */
    public function getMerchantInformation(Request $request, Context $context): JsonResponse
    {
        $salesChannelId = $request->query->getAlnum('salesChannelId') ?: null;

        $response = $this->merchantIntegrationsService->getMerchantInformation($context, $salesChannelId);

        return new JsonResponse($response);
    }
}
