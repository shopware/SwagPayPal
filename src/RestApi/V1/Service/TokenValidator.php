<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Token;

#[Package('checkout')]
class TokenValidator
{
    public function isTokenValid(Token $token): bool
    {
        $dateTimeNow = new \DateTime();
        $dateTimeExpire = $token->getExpireDateTime();
        // Decrease expire date by one hour just to make sure, it doesn't run into an unauthorized exception.
        $dateTimeExpire = $dateTimeExpire->sub(new \DateInterval('PT1H'));

        return $dateTimeExpire > $dateTimeNow;
    }
}
