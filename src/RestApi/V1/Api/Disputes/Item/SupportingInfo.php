<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\PayPalApiStruct;

class SupportingInfo extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $notes;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $providedTime;

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getProvidedTime(): string
    {
        return $this->providedTime;
    }

    public function setProvidedTime(string $providedTime): void
    {
        $this->providedTime = $providedTime;
    }
}
