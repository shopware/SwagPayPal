<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Controller;

use SwagPayPal\Setting\Service\ApiCredentialTestServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SettingsController extends AbstractController
{
    /**
     * @var ApiCredentialTestServiceInterface
     */
    private $apiCredentialTestService;

    public function __construct(ApiCredentialTestServiceInterface $apiService)
    {
        $this->apiCredentialTestService = $apiService;
    }

    /**
     * @Route("/api/v{version}/_action/paypal/validate-api-credentials", name="api.action.paypal.validate.api.credentials", methods={"POST"})
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
