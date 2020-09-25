<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Payload;

use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Webhook\Payload\AbstractPayload\AbstractUpdated;

abstract class AbstractPayload extends PosStruct
{
    /**
     * @var string
     */
    protected $organizationUuid;

    /**
     * @var AbstractUpdated
     */
    protected $updated;

    public function getOrganizationUuid(): string
    {
        return $this->organizationUuid;
    }

    public function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }

    public function getUpdated(): AbstractUpdated
    {
        return $this->updated;
    }

    public function setUpdated(AbstractUpdated $updated): void
    {
        $this->updated = $updated;
    }
}
