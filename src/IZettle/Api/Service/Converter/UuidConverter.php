<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Converter;

class UuidConverter
{
    public function convertUuidToV1(string $uuid): string
    {
        if (mb_strlen($uuid) !== 32) {
            return '';
        }

        $uuid = substr_replace($uuid, '1', 12, 1);

        return sprintf('%s-%s-%s-%s-%s', mb_substr($uuid, 0, 8), mb_substr($uuid, 8, 4), mb_substr($uuid, 12, 4), mb_substr($uuid, 16, 4), mb_substr($uuid, 20));
    }
}
