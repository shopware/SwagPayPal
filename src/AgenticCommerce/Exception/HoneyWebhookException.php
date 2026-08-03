<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Exception;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\HoneyWebhookResult;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class HoneyWebhookException extends HttpException
{
    public const API_ERROR = 'API_ERROR';
    public const NOT_REGISTERED = 'NOT_REGISTERED';
    public const SALES_CHANNEL_NOT_FOUND = 'SALES_CHANNEL_NOT_FOUND';
    public const PRODUCT_EXPORT_NOT_FOUND = 'PRODUCT_EXPORT_NOT_FOUND';
    public const STOREFRONT_SALES_CHANNEL_NOT_FOUND = 'STOREFRONT_SALES_CHANNEL_NOT_FOUND';
    public const INVALID_PRODUCT_EXPORT_ROUTE = 'INVALID_PRODUCT_EXPORT_ROUTE';

    public static function salesChannelNotRegistered(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NOT_REGISTERED,
            'Sales channel is not registered and can\'t be deregistered'
        );
    }

    public static function invalidSalesChannel(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::SALES_CHANNEL_NOT_FOUND,
            'Agent commerce sales channel not found'
        );
    }

    public static function productExportNotFound(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::PRODUCT_EXPORT_NOT_FOUND,
            'Product export sales channel not found'
        );
    }

    public static function storefrontSalesChannelNotFound(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::STOREFRONT_SALES_CHANNEL_NOT_FOUND,
            'Storefront sales channel not found'
        );
    }

    public static function invalidProductExportRoute(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_PRODUCT_EXPORT_ROUTE,
            'Invalid product export route'
        );
    }

    public static function invalidRequest(HoneyWebhookResult $result): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::API_ERROR . '_' . $result->error,
            $result->message,
            previous: $result->exception,
        );
    }
}
