<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\SalesChannel\Response;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @extends StoreApiResponse<ArrayStruct<array{id: string}>>
 */
#[Package('checkout')]
final class AgentCartResponse extends StoreApiResponse
{
    public function __construct(
        string $token
    ) {
        parent::__construct(
            new ArrayStruct([
                'id' => $token,
            ])
        );
    }
}
