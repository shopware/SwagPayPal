<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

class ApplicationContext
{
    /**
     * @var string
     */
    private $brandName;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $userAction = 'commit';

    /**
     * @return string
     */
    public function getBrandName(): string
    {
        return $this->brandName;
    }

    /**
     * @param string $brandName
     */
    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getUserAction(): string
    {
        return $this->userAction;
    }

    /**
     * @param string $userAction
     */
    public function setUserAction(string $userAction): void
    {
        $this->userAction = $userAction;
    }

    public function toArray(): array
    {
        return [
            'brand_name' => $this->getBrandName(),
            'locale' => $this->getLocale(),
            'user_action' => $this->getUserAction(),
        ];
    }
}
