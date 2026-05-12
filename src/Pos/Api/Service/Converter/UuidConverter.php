<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service\Converter;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('checkout')]
class UuidConverter
{
    public function convertUuidToV1(string $uuid): string
    {
        if (!Uuid::isValid($uuid)) {
            throw new InvalidUuidException($uuid);
        }

        $uuid = \substr_replace($uuid, '1', 12, 1);

        return \sprintf('%s-%s-%s-%s-%s', \mb_substr($uuid, 0, 8), \mb_substr($uuid, 8, 4), \mb_substr($uuid, 12, 4), \mb_substr($uuid, 16, 4), \mb_substr($uuid, 20));
    }

    public function convertUuidToV4(string $uuid): string
    {
        $uuid = \str_replace('-', '', $uuid);

        $uuid = \substr_replace($uuid, '4', 12, 1);

        if (!Uuid::isValid($uuid)) {
            throw new InvalidUuidException($uuid);
        }

        return $uuid;
    }

    public function convertUuidToV7(string $uuid): string
    {
        $uuid = \str_replace('-', '', $uuid);

        $uuid = \substr_replace($uuid, '7', 12, 1);

        if (!Uuid::isValid($uuid)) {
            throw new InvalidUuidException($uuid);
        }

        return $uuid;
    }

    public function incrementUuid(string $uuid): string
    {
        if (!Uuid::isValid($uuid)) {
            throw new InvalidUuidException($uuid);
        }

        $lastDigit = \mb_substr(\dechex((int) \hexdec(\mb_substr($uuid, -1)) + 1), -1);

        return \mb_substr($uuid, 0, 31) . $lastDigit;
    }
}
