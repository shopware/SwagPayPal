<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @deprecated tag:v6.0.0 - will be removed
 */
class CurrencyInstaller
{
    private EntityRepositoryInterface $currencyRepository;

    public function __construct(EntityRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    public function install(Context $context): void
    {
        $currencies = $this->getDefaultCurrencies();

        foreach ($currencies as $currency) {
            try {
                $this->currencyRepository->create([$currency], $context);
            } catch (UniqueConstraintViolationException $e) {
                // currency already exists, no need to import
            }
        }
    }

    private function getDefaultCurrencies(): array
    {
        /** @var string $currencies */
        $currencies = \file_get_contents(__DIR__ . '/data/currencies.json');

        return \json_decode($currencies, true);
    }
}
