<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PaymentsApi\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\PaymentsApi\Service\TransactionValidator;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;
use Swag\PayPal\RestApi\V1\Api\Common\Details;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\ItemCollection;

/**
 * @internal
 */
#[Package('checkout')]
class TransactionValidatorTest extends TestCase
{
    #[DataProvider('dataProviderTestValidateItemList')]
    public function testValidateItemList(
        string $subTotal,
        string $shippingTotal,
        string $totalTax,
        string $totalAmount,
        string $itemPerPiecePrice,
        string $itemPerPieceTax,
        int $itemQuantity,
        bool $expectedResult
    ): void {
        $transaction = new Transaction();

        $details = new Details();
        $details->setSubtotal($subTotal);
        $details->setShipping($shippingTotal);
        $details->setTax($totalTax);

        $amount = new Amount();
        $amount->setTotal($totalAmount);
        $amount->setCurrency('EUR');
        $amount->setDetails($details);

        $item = new Item();
        $item->setPrice($itemPerPiecePrice);
        $item->setTax($itemPerPieceTax);
        $item->setQuantity($itemQuantity);

        $itemList = new ItemList();
        $itemList->setItems(new ItemCollection([$item]));

        $transaction->setAmount($amount);
        $transaction->setItemList($itemList);

        static::assertSame($expectedResult, TransactionValidator::validateItemList([$transaction]));
    }

    public static function dataProviderTestValidateItemList(): array
    {
        return [
            [
                '760.67',
                '4.99',
                '12',
                '777.66',
                '760.67',
                '12',
                1,
                true,
            ],
            [
                '42.01',
                '4.99',
                '7.98',
                '54.98',
                '42.01',
                '7.98',
                1,
                true,
            ],
            [
                '42.00',
                '4.99',
                '7.98',
                '54.98',
                '42.01',
                '7.98',
                1,
                false,
            ],
            [
                '760.66',
                '4.98',
                '12',
                '777.66',
                '760.67',
                '12',
                1,
                false,
            ],
            [
                '709.35',
                '0',
                '49.65',
                '759',
                '236.45',
                '16.55',
                3,
                true,
            ],
        ];
    }
}
