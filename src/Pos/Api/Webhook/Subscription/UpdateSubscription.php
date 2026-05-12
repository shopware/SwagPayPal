<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Webhook\Subscription;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
class UpdateSubscription extends PosStruct
{
    /**
     * @var string[]
     */
    protected array $eventNames;

    protected string $destination;

    protected string $contactEmail;

    /**
     * @return string[]
     */
    public function getEventNames(): array
    {
        return $this->eventNames;
    }

    /**
     * @param string[] $eventNames
     */
    public function setEventNames(array $eventNames): void
    {
        $this->eventNames = $eventNames;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }
}
