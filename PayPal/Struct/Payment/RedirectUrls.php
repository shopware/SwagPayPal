<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

class RedirectUrls
{
    /**
     * @var string
     */
    private $returnUrl;

    /**
     * @var string
     */
    private $cancelUrl;

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

    public static function fromArray(array $data = null): RedirectUrls
    {
        $result = new self();

        if ($data === null) {
            return $result;
        }

        $result->setCancelUrl($data['cancel_url']);
        $result->setReturnUrl($data['return_url']);

        return $result;
    }

    public function toArray(): array
    {
        return [
            'return_url' => $this->getReturnUrl(),
            'cancel_url' => $this->getCancelUrl(),
        ];
    }
}
