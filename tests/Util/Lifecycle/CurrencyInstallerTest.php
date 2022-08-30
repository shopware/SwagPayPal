<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Util\Lifecycle\Installer\CurrencyInstaller;

class CurrencyInstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private EntityRepositoryInterface $currencyRepository;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @dataProvider providerCurrencies
     */
    public function testInstall(string $currencyCode): void
    {
        $id = $this->getCurrencyId($currencyCode);
        if ($id !== null) {
            $this->currencyRepository->delete([['id' => $id]], Context::createDefaultContext());
        }

        static::assertNull($this->getCurrencyId($currencyCode));
        $installer = new CurrencyInstaller($this->currencyRepository);
        $installer->install(Context::createDefaultContext());
        static::assertNotNull($this->getCurrencyId($currencyCode));
    }

    /**
     * @dataProvider providerCurrencies
     */
    public function testInstallExists(string $currencyCode): void
    {
        static::assertNotNull($this->getCurrencyId($currencyCode));
        $installer = new CurrencyInstaller($this->currencyRepository);
        $installer->install(Context::createDefaultContext());
        static::assertNotNull($this->getCurrencyId($currencyCode));
    }

    /**
     * @return string[][]
     */
    public function providerCurrencies(): array
    {
        return [
            ['MXN'],
            ['AUD'],
            ['BRL'],
        ];
    }

    private function getCurrencyId(string $code): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $code));

        /** @var string|null $id */
        $id = $this->currencyRepository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }
}
