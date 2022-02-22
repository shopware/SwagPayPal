<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Context;
use Swag\PayPal\Checkout\APM\APMCheckoutMethodInterface;
use Swag\PayPal\Checkout\APM\Service\AbstractAPMCheckoutDataService;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\SEPA\Service\SEPACheckoutDataService;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations\Capability;

class SEPAMethodData extends AbstractMethodData implements APMCheckoutMethodInterface
{
    public const PAYPAL_SEPA_FIELD_DATA_EXTENSION_ID = 'payPalSEPAFieldData';

    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'SEPA Lastschrift',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'SEPA direct debit',
            ],
        ];
    }

    public function getPosition(): int
    {
        return -97;
    }

    /**
     * @return class-string
     */
    public function getHandler(): string
    {
        return SEPAHandler::class;
    }

    public function getRuleData(Context $context): ?array
    {
        return null;
    }

    public function getInitialState(): bool
    {
        return false;
    }

    public function getMediaFileName(): ?string
    {
        return 'sepa';
    }

    public function getCheckoutDataService(): AbstractAPMCheckoutDataService
    {
        /** @var SEPACheckoutDataService $service */
        $service = $this->container->get(SEPACheckoutDataService::class);

        return $service;
    }

    public function getCheckoutTemplateExtensionId(): string
    {
        return self::PAYPAL_SEPA_FIELD_DATA_EXTENSION_ID;
    }

    public function validateCapability(MerchantIntegrations $merchantIntegrations): string
    {
        $capability = $merchantIntegrations->getSpecificCapability('ALT_PAY_PROCESSING');
        if ($capability !== null && $capability->getStatus() === Capability::STATUS_ACTIVE) {
            return self::CAPABILITY_ACTIVE;
        }

        return self::CAPABILITY_INACTIVE;
    }
}
