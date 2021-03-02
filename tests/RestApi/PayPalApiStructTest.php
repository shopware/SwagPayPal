<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Test\RestApi\_fixtures\TestStruct;

class PayPalApiStructTest extends TestCase
{
    use AssertArraySubsetTrait;

    public function testAssign(): void
    {
        $data = [
            'id' => 'testId',
            'bar' => [
                'bar' => 'testBar',
            ],
            'foo' => [
                [
                    'foo_baz' => 'fooBazTest',
                ],
            ],
        ];

        $testStruct = new TestStruct();
        $testStruct->assign($data);

        $testJsonString = \json_encode($testStruct);
        static::assertNotFalse($testJsonString);

        $testArray = \json_decode($testJsonString, true);

        static::assertArraySubset($data, $testArray);
    }

    public function testAssignWithNoGetter(): void
    {
        $data = [
            'no_setter' => 'testValue',
            'not_existing_class' => [
                'test' => 'value',
            ],
            'not_existing_collection_class' => [
                [
                    'test' => 'value',
                ],
            ],
        ];

        $paypalStruct = new TestStruct();
        $paypalStruct->assign($data);

        $testJsonString = \json_encode($paypalStruct);
        static::assertNotFalse($testJsonString);

        $paypalStructArray = \json_decode($testJsonString, true);

        static::assertNull($paypalStructArray['id']);
        static::assertNull($paypalStructArray['bar']);
        static::assertNull($paypalStructArray['foo']);
        static::assertNull($paypalStructArray['not_existing_class']);
        static::assertEmpty($paypalStructArray['not_existing_collection_class']);
    }
}
