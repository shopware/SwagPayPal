<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Inventory;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class Location extends IZettleStruct
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var bool
     */
    protected $default;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    protected function setType(string $type): void
    {
        $this->type = $type;
    }

    protected function setName(string $name): void
    {
        $this->name = $name;
    }

    protected function setDescription(string $description): void
    {
        $this->description = $description;
    }

    protected function setDefault(bool $default): void
    {
        $this->default = $default;
    }
}
