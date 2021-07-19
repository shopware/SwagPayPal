<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Authentication\ApiKey;

use Swag\PayPal\Pos\Api\Common\PosStruct;

/**
 * JWT Header
 */
class Header extends PosStruct
{
    /**
     * Key ID
     *
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $kid;

    /**
     * Type
     *
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $typ;

    /**
     * Algorithm
     *
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $alg;

    public function getKid(): string
    {
        return $this->kid;
    }

    public function setKid(string $kid): void
    {
        $this->kid = $kid;
    }

    public function getTyp(): string
    {
        return $this->typ;
    }

    public function setTyp(string $typ): void
    {
        $this->typ = $typ;
    }

    public function getAlg(): string
    {
        return $this->alg;
    }

    public function setAlg(string $alg): void
    {
        $this->alg = $alg;
    }
}
