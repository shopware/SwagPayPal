<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Swag\PayPal\RestApi\V2\Api\Patch;

/**
 * @deprecated tag:v6.0.0 - will be removed, use PurchaseUnitPatchBuilder instead
 */
class CustomIdPatchBuilder
{
    public function createCustomIdPatch(string $customId): Patch
    {
        return (new Patch())->assign([
            'op' => Patch::OPERATION_ADD,
            'path' => '/purchase_units/@reference_id==\'default\'/custom_id',
            'value' => $customId,
        ]);
    }
}
