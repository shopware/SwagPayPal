<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Webhook\Payload;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Webhook\Payload\AbstractPayload\AbstractUpdated;

abstract class AbstractPayload extends IZettleStruct
{
    /**
     * @var string
     */
    protected $organizationUuid;

    /**
     * @var AbstractUpdated
     */
    protected $updated;

    public function getUpdated(): AbstractUpdated
    {
        return $this->updated;
    }

    protected function setOrganizationUuid(string $organizationUuid): void
    {
        $this->organizationUuid = $organizationUuid;
    }

    protected function setUpdated(AbstractUpdated $updated): void
    {
        $this->updated = $updated;
    }
}
