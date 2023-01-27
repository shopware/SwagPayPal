<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Patch;

use Swag\PayPal\RestApi\V1\Api\Patch;

/**
 * @deprecated tag:v6.0.0 - will be removed, use Swag\PayPal\RestApi\V1\Api\Patch\TransactionPatchBuilder instead
 */
class CustomTransactionPatchBuilder
{
    public function createCustomTransactionPatch(string $orderTransactionId): Patch
    {
        return (new Patch())->assign([
            'op' => Patch::OPERATION_ADD,
            'path' => '/transactions/0/custom',
            'value' => $orderTransactionId,
        ]);
    }
}
