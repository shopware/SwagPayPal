<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

class OAuthCredentials
{
    /**
     * @var string
     */
    protected $restId;

    /**
     * @var string
     */
    protected $restSecret;

    protected string $url;

    public function __toString(): string
    {
        return \sprintf('Basic %s', \base64_encode($this->restId . ':' . $this->restSecret));
    }

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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
