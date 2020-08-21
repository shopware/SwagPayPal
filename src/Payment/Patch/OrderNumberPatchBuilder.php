<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Payment\Patch;

use Swag\PayPal\PayPal\Api\Patch;

class OrderNumberPatchBuilder
{
    public function createOrderNumberPatch(string $orderNumber): Patch
    {
        return (new Patch())->assign([
            'op' => Patch::OPERATION_ADD,
            'path' => '/transactions/0/invoice_number',
            'value' => $orderNumber,
        ]);
    }
}
