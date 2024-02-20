<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

#[OA\Schema(schema: 'swag_paypal_v1_oauth_credentials')]
#[Package('checkout')]
class OAuthCredentials
{
    #[OA\Property(type: 'string')]
    protected string $restId;

    #[OA\Property(type: 'string')]
    protected string $restSecret;

    #[OA\Property(type: 'string')]
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

    public static function createFromRestCredentials(string $clientId, string $clientSecret, string $url): self
    {
        $credentials = new self();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);
        $credentials->setUrl($url);

        return $credentials;
    }

    public static function createEmpty(string $url): self
    {
        $credentials = new self();
        $credentials->setRestId('');
        $credentials->setRestSecret('');
        $credentials->setUrl($url);

        return $credentials;
    }
}
