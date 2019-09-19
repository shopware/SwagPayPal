<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api;

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

    public function __toString(): string
    {
        return 'Basic ' . base64_encode($this->restId . ':' . $this->restSecret);
    }

    public function setRestId(string $restId): void
    {
        $this->restId = $restId;
    }

    public function setRestSecret(string $restSecret): void
    {
        $this->restSecret = $restSecret;
    }
}
