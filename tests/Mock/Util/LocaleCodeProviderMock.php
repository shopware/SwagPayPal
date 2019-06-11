<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\Util\LocaleCodeProvider;

class LocaleCodeProviderMock extends LocaleCodeProvider
{
    public function __construct(EntityRepositoryInterface $entityRepository)
    {
        parent::__construct($entityRepository);
    }

    public function getLocaleCodeFromContext(Context $context): string
    {
        return 'en-GB';
    }
}
