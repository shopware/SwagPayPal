<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class TokenResponse extends StoreApiResponse
{
    /**
     * @var ArrayStruct<string, string>
     */
    protected $object;

    public function __construct(string $token)
    {
        parent::__construct(new ArrayStruct(['token' => $token]));
    }

    public function getToken(): string
    {
        return $this->object->get('token');
    }
}
