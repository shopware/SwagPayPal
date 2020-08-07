<?php
declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Subscription;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class ApplicationContext extends PayPalStruct
{
    public const USER_ACTION_TYPE_SUBSCRIBE_NOW = 'SUBSCRIBE_NOW';
    public const USER_ACTION_TYPE_CONTINUE = 'CONTINUE';

    /** @var string */
    protected $userAction = self::USER_ACTION_TYPE_SUBSCRIBE_NOW;

    /** @var string */
    protected $brandName;

    /** @var string */
    protected $locale;

    /** @var string */
    protected $shippingPreference = 'SET_PROVIDED_ADDRESS';

    /** @var string */
    protected $returnUrl;

    /** @var string */
    protected $cancelUrl;

    public function getUserAction(): string
    {
        return $this->userAction;
    }

    public function setUserAction(string $userAction): self
    {
        $this->userAction = $userAction;

        return $this;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): self
    {
        $this->brandName = $brandName;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getShippingPreference(): string
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(string $shippingPreference): self
    {
        $this->shippingPreference = $shippingPreference;

        return $this;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getCancelUrl(): string
    {
        return $this->cancelUrl;
    }

    public function setCancelUrl(string $cancelUrl): self
    {
        $this->cancelUrl = $cancelUrl;

        return $this;
    }
}
