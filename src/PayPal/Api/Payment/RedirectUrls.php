<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class RedirectUrls extends PayPalStruct
{
    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var string
     */
    protected $cancelUrl;

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    public function setCancelUrl(string $cancelUrl): void
    {
        $this->cancelUrl = $cancelUrl;
    }
}
