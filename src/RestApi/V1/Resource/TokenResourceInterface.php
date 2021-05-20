<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Token;

interface TokenResourceInterface
{
    /**
     * @deprecated tag:v4.0.0 - parameter $url will be removed, is placed in OAuthCredentials now
     */
    public function getToken(OAuthCredentials $credentials, string $url): Token;
}
