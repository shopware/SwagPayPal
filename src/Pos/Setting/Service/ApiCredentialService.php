<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Service;

use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Resource\TokenResource;

class ApiCredentialService
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    public function __construct(TokenResource $tokenResource)
    {
        $this->tokenResource = $tokenResource;
    }

    public function testApiCredentials(string $apiKey): bool
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return $this->tokenResource->testApiCredentials($credentials);
    }
}
