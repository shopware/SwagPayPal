<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api;

class OAuthCredentials
{
    /**
     * @var string
     */
    protected $clientId = '456dadab-3085-4fa3-bf2b-a2efd01c3593';

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
