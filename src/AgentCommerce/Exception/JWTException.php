<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[Package('checkout')]
class JWTException extends HttpException
{
    private const INVALID_JWT = 'UTIL__INVALID_JWT';

    public static function invalidJwt(string $reason, ?\Exception $e = null): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_JWT,
            (!str_contains($reason, 'Invalid JWT: ') ? 'Invalid JWT: ' : '') . '{{ message }}',
            ['message' => $reason],
            $e
        );
    }
}
