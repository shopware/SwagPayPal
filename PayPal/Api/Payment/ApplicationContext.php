<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment;

use SwagPayPal\PayPal\Api\PayPalStruct;

class ApplicationContext extends PayPalStruct
{
    public const LANDINGPAGE_TYPE_LOGIN = 'Login';

    public const LANDINGPAGE_TYPE_BILLING = 'Billing';

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
    protected $userAction = 'commit';

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

    protected function setUserAction(string $userAction): void
    {
        $this->userAction = $userAction;
    }
}
