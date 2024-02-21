<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class VaultData extends Struct
{
    public const SNIPPET_TYPE_ACCOUNT = 'account';
    public const SNIPPET_TYPE_CARD = 'card';

    protected ?string $identifier = null;

    protected string $snippetType = self::SNIPPET_TYPE_ACCOUNT;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getSnippetType(): string
    {
        return $this->snippetType;
    }

    public function setSnippetType(string $snippetType): void
    {
        $this->snippetType = $snippetType;
    }
}
