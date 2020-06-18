<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Swag\PayPal\IZettle\Setting\Service\ApiCredentialService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(ApiCredentialService $apiService)
    {
        $this->apiCredentialService = $apiService;
    }

    /**
     * @Route("/api/v{version}/_action/paypal/izettle/validate-api-credentials", name="api.action.paypal.izettle.validate.api.credentials", methods={"POST"})
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $apiKey = $request->request->get('apiKey');

        $credentialsValid = $this->apiCredentialService->testApiCredentials($apiKey);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }
}
