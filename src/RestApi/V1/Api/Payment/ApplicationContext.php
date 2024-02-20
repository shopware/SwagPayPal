<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_payment_application_context')]
#[Package('checkout')]
class ApplicationContext extends PayPalApiStruct
{
    public const LANDING_PAGE_TYPE_LOGIN = 'Login';
    public const LANDING_PAGE_TYPE_BILLING = 'Billing';
    public const LANDING_PAGE_TYPES = [
        self::LANDING_PAGE_TYPE_LOGIN,
        self::LANDING_PAGE_TYPE_BILLING,
    ];

    public const USER_ACTION_TYPE_COMMIT = 'commit';
    public const USER_ACTION_TYPE_CONTINUE = 'continue';

    #[OA\Property(type: 'string')]
    protected string $brandName;

    #[OA\Property(type: 'string')]
    protected string $locale;

    #[OA\Property(type: 'string', enum: self::LANDING_PAGE_TYPES)]
    protected string $landingPage;

    #[OA\Property(type: 'string', default: 'SET_PROVIDED_ADDRESS')]
    protected string $shippingPreference = 'SET_PROVIDED_ADDRESS';

    #[OA\Property(type: 'string', default: self::USER_ACTION_TYPE_COMMIT)]
    protected string $userAction = self::USER_ACTION_TYPE_COMMIT;

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

    public function getLandingPage(): string
    {
        return $this->landingPage;
    }

    public function setLandingPage(string $landingPage): void
    {
        $this->landingPage = $landingPage;
    }

    public function getShippingPreference(): string
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(string $shippingPreference): void
    {
        $this->shippingPreference = $shippingPreference;
    }

    public function getUserAction(): string
    {
        return $this->userAction;
    }

    public function setUserAction(string $userAction): void
    {
        $this->userAction = $userAction;
    }
}
