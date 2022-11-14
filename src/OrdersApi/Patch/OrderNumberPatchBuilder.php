<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Swag\PayPal\RestApi\V2\Api\Patch;

class OrderNumberPatchBuilder
{
    public function createOrderNumberPatch(string $orderNumber): Patch
    {
        return (new Patch())->assign([
            'op' => Patch::OPERATION_ADD,
            'path' => '/purchase_units/@reference_id==\'default\'/invoice_id',
            'value' => $orderNumber,
        ]);
    }

    public function createRemoveOrderNumberPatch(): Patch
    {
        return (new Patch())->assign([
            'op' => Patch::OPERATION_REMOVE,
            'path' => '/purchase_units/@reference_id==\'default\'/invoice_id',
        ]);
    }
}
