<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
abstract class AbstractPayload extends PosStruct
{
    protected string $organizationUuid;

    public function getOrganizationUuid(): string
    {
        return $this->organizationUuid;
    }

    public function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }
}
