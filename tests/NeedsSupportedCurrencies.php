<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SwipeStripe\Price\SupportedCurrencies\SingleSupportedCurrency;
use SwipeStripe\Price\SupportedCurrencies\SupportedCurrenciesInterface;

/**
 * Trait NeedsSupportedCurrencies
 * @package SwipeStripe\Shipping\Tests
 */
trait NeedsSupportedCurrencies
{
    /**
     *
     */
    protected static function setupSupportedCurrencies(): void
    {
        Config::modify()->set(SingleSupportedCurrency::class, 'shop_currency', 'NZD');
        Injector::inst()->registerService(new SingleSupportedCurrency(), SupportedCurrenciesInterface::class);
    }

    /**
     * @return SupportedCurrenciesInterface
     */
    protected function getSupportedCurrencies(): SupportedCurrenciesInterface
    {
        return Injector::inst()->get(SupportedCurrenciesInterface::class);
    }
}
