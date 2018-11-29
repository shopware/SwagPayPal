<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Controller;

use SwagPayPal\Service\ApiCredentialTestService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends Controller
{
    private $apiCredentialTestService;

    public function __construct(ApiCredentialTestService $apiService)
    {
        $this->apiCredentialTestService = $apiService;
    }

    /**
     * @Route("/api/v{version}/paypal/validateApiCredentials", name="validate.paypal.api.credentials", methods={"POST"})
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $clientId = $request->request->get('clientId');
        $clientSecret = $request->request->get('clientSecret');
        $sandboxActive = $request->request->getBoolean('sandboxActive');

        $credentialsValid = $this->apiCredentialTestService->testApiCredentials($clientId, $clientSecret, $sandboxActive);

        return new JsonResponse(['credentialsValid' => $credentialsValid]);
    }
}
