<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Common;

class Link
{
    /**
     * @var string
     */
    private $href;

    /**
     * @var string
     */
    private $rel;

    /**
     * @var string
     */
    private $method;

    public function getHref(): string
    {
        return $this->href;
    }

    public function setHref(string $href): void
    {
        $this->href = $href;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function setRel(string $rel): void
    {
        $this->rel = $rel;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function toArray(): array
    {
        return [
            'href' => $this->getHref(),
            'rel' => $this->getRel(),
            'method' => $this->getMethod(),
        ];
    }

    public static function fromArray(array $data): Link
    {
        $result = new self();
        $result->setHref($data['href']);
        $result->setRel($data['rel']);
        $result->setMethod($data['method']);

        return $result;
    }
}
