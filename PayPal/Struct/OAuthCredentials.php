<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct;

class OAuthCredentials
{
    /**
     * @var string
     */
    private $restId;

    /**
     * @var string
     */
    private $restSecret;

    public function getRestId(): string
    {
        return $this->restId;
    }

    public function setRestId(string $restId): void
    {
        $this->restId = $restId;
    }

    public function getRestSecret(): string
    {
        return $this->restSecret;
    }

    public function setRestSecret(string $restSecret): void
    {
        $this->restSecret = $restSecret;
    }

    public function toString(): string
    {
        return 'Basic ' . base64_encode($this->restId . ':' . $this->restSecret);
    }
}
