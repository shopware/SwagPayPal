<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Setting\Service;

use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SystemConfigControllerMock extends SystemConfigController
{
    /**
     * @var Request|null
     */
    private $lastRequest;

    public function __construct()
    {
    }

    public function getLastRequest(): ?Request
    {
        return $this->lastRequest;
    }

    public function saveConfiguration(Request $request): JsonResponse
    {
        $this->lastRequest = $request;

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    public function batchSaveConfiguration(Request $request): JsonResponse
    {
        $this->lastRequest = $request;

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
