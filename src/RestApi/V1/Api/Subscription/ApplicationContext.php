<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Subscription;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @codeCoverageIgnore
 *
 * @experimental
 *
 * This class is experimental and not officially supported.
 * It is currently not used within the plugin itself. Use with caution.
 */
#[OA\Schema(schema: 'swag_paypal_v1_subscription_application_context')]
#[Package('checkout')]
class ApplicationContext extends PayPalApiStruct
{
    public const USER_ACTION_TYPE_SUBSCRIBE_NOW = 'SUBSCRIBE_NOW';
    public const USER_ACTION_TYPE_CONTINUE = 'CONTINUE';

    #[OA\Property(type: 'string', default: self::USER_ACTION_TYPE_SUBSCRIBE_NOW)]
    protected string $userAction = self::USER_ACTION_TYPE_SUBSCRIBE_NOW;

    #[OA\Property(type: 'string')]
    protected string $brandName;

    #[OA\Property(type: 'string')]
    protected string $locale;

    #[OA\Property(type: 'string', default: 'SET_PROVIDED_ADDRESS')]
    protected string $shippingPreference = 'SET_PROVIDED_ADDRESS';

    #[OA\Property(type: 'string')]
    protected string $returnUrl;

    #[OA\Property(type: 'string')]
    protected string $cancelUrl;

    public function getUserAction(): string
    {
        return $this->userAction;
    }

    public function setUserAction(string $userAction): void
    {
        $this->userAction = $userAction;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getShippingPreference(): string
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(string $shippingPreference): void
    {
        $this->shippingPreference = $shippingPreference;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    public function getCancelUrl(): string
    {
        return $this->cancelUrl;
    }

    public function setCancelUrl(string $cancelUrl): void
    {
        $this->cancelUrl = $cancelUrl;
    }
}
