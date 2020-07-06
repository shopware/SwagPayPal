<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class CreateProductFixture
{
    /**
     * @var IZettleStruct[]
     */
    public static $lastCreatedProducts = [];

    public static function post(?IZettleStruct $data): ?array
    {
        TestCase::assertNotNull($data);
        self::$lastCreatedProducts[] = $data;

        return null;
    }
}
