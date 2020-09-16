<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;

class PayPalClientFactory implements PayPalClientFactoryInterface
{
    /**
     * @var TokenResourceInterface
     */
    private $tokenResource;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PayPalClient|null
     */
    private $payPalClient;

    public function __construct(
        TokenResourceInterface $tokenResource,
        SettingsServiceInterface $settingsService,
        LoggerInterface $logger
    ) {
        $this->tokenResource = $tokenResource;
        $this->settingsService = $settingsService;
        $this->logger = $logger;
    }

    public function getPayPalClient(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): PayPalClientInterface {
        $settings = $this->settingsService->getSettings($salesChannelId);

        if ($this->payPalClient === null) {
            $this->payPalClient = new PayPalClient(
                $this->tokenResource,
                $settings,
                $this->logger,
                $partnerAttributionId
            );
        }

        return $this->payPalClient;
    }
}
