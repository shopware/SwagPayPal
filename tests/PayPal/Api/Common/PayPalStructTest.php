<?php declare(strict_types=1);

namespace SwagPayPal\Test\PayPal\Api\Common;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\AssertArraySubsetBehaviour;
use SwagPayPal\Test\PayPal\Api\Common\_fixtures\TestStruct;

class PayPalStructTest extends TestCase
{
    use AssertArraySubsetBehaviour;

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

        $testJsonString = json_encode($testStruct);
        static::assertNotFalse($testJsonString);
        if ($testJsonString === false) {
            return;
        }

        $testArray = json_decode($testJsonString, true);

        $this->silentAssertArraySubset($data, $testArray);
    }

    public function testAssignWithNoGetter(): void
    {
        $testStruct = new TestStruct();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('setter method for "NoSetter" not found');
        $testStruct->assign(['noSetter' => 'testValue']);
    }
}
