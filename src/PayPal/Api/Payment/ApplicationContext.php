<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class ApplicationContext extends PayPalStruct
{
    public const LANDING_PAGE_TYPE_LOGIN = 'Login';
    public const LANDING_PAGE_TYPE_BILLING = 'Billing';

    public const USER_ACTION_TYPE_COMMIT = 'commit';
    public const USER_ACTION_TYPE_CONTINUE = 'continue';

    /**
     * @var string
     */
    protected $brandName;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $landingPage;

    /**
     * @var string
     */
    protected $shippingPreference = 'SET_PROVIDED_ADDRESS';

    /**
     * @var string
     */
    protected $userAction = self::USER_ACTION_TYPE_COMMIT;

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function setLandingPage(string $landingPageType): void
    {
        $this->landingPage = $landingPageType;
    }

    public function setUserAction(string $userAction): void
    {
        $this->userAction = $userAction;
    }
}
