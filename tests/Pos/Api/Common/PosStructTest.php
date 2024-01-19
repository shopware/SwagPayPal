<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Api\Common;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Test\Pos\Api\Common\_fixtures\TestStruct;
use Swag\PayPal\Test\RestApi\AssertArraySubsetTrait;

/**
 * @internal
 */
#[Package('checkout')]
class PosStructTest extends TestCase
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
                    'fooBoo' => 'fooBooTest',
                ],
                null,
            ],
        ];

        $testStruct = new TestStruct();
        $testStruct->assign($data);

        $testJsonString = \json_encode($testStruct);
        static::assertNotFalse($testJsonString);

        unset($data['foo'][0]['foo_baz']);
        $data['foo'][0]['fooBaz'] = 'fooBazTest';
        unset($data['foo'][1]);

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
                null,
            ],
        ];

        $paypalStruct = new TestStruct();
        $paypalStruct->assign($data);

        $testJsonString = \json_encode($paypalStruct);
        static::assertNotFalse($testJsonString);

        $posStructArray = \json_decode($testJsonString, true);

        unset($data['not_existing_collection_class'][1]);

        static::assertNull($posStructArray['id']);
        static::assertNull($posStructArray['bar']);
        static::assertNull($posStructArray['foo']);
        static::assertNull($posStructArray['notExistingClass']);
        static::assertSame($posStructArray['notExistingCollectionClass'], $data['not_existing_collection_class']);
        static::assertIsArray($posStructArray['notExistingCollectionClass']);
    }
}
