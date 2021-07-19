<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Pos\Api\Common\PosStruct;

class CreateProductFixture
{
    /**
     * @var PosStruct[]
     */
    public static array $lastCreatedProducts = [];

    public static function post(?PosStruct $data): ?array
    {
        TestCase::assertNotNull($data);
        self::$lastCreatedProducts[] = $data;

        return null;
    }
}
