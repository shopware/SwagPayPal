<?php declare(strict_types=1);

namespace Swag\PayPal\Setting\Service;

interface ApiCredentialServiceInterface
{
    public function testApiCredentials(string $clientId, string $clientSecret, bool $sandboxActive): bool;

    public function getApiCredentials(string $authCode, string $sharedId, string $nonce, bool $sandboxActive): array;
}
