<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Client;

use Monolog\Logger;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;

class PayPalClientFactory
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsProvider;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        TokenResource $tokenResource,
        SettingsServiceInterface $settingsProvider,
        Logger $logger
    ) {
        $this->tokenResource = $tokenResource;
        $this->settingsProvider = $settingsProvider;
        $this->logger = $logger;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function createPaymentClient(?string $salesChannelId, string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC): PayPalClient
    {
        $settings = $this->settingsProvider->getSettings($salesChannelId);

        return new PayPalClient($this->tokenResource, $settings, $this->logger, $partnerAttributionId);
    }
}
