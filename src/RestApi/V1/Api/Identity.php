<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Address;

/**
 * @OA\Schema(schema="swag_paypal_v1_identity")
 */
class Identity extends PayPalApiStruct
{
    /**
     * @OA\Property(type="string")
     */
    protected string $userId;

    /**
     * @OA\Property(type="string")
     */
    protected string $sub;

    /**
     * @OA\Property(type="string")
     */
    protected string $name;

    /**
     * @OA\Property(type="string")
     */
    protected string $payerId;

    /**
     * @OA\Property(type="array")
     */
    protected array $address;

    /**
     * @OA\Property(type="string")
     */
    protected string $verifiedAccount;

    /**
     * @OA\Property(type="array")
     */
    protected array $emails;

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getSub(): string
    {
        return $this->sub;
    }

    public function setSub(string $sub): void
    {
        $this->sub = $sub;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    /**
     * @return Address[]
     */
    public function getAddress(): array
    {
        return $this->address;
    }

    public function setAddress(array $address): void
    {
        $this->address = $address;
    }

    public function getVerifiedAccount(): string
    {
        return $this->verifiedAccount;
    }

    public function setVerifiedAccount(string $verifiedAccount): void
    {
        $this->verifiedAccount = $verifiedAccount;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function setEmails(array $emails): void
    {
        $this->emails = $emails;
    }
}
