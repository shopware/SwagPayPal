<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class LanguageNotFoundException extends ShopwareHttpException
{
    public function __construct(string $languageCode)
    {
        parent::__construct(
            'Language entity with code "{{ languageCode }}" not found',
            ['languageCode' => $languageCode]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_IZETTLE__LANGUAGE_NOT_FOUND';
    }
}
