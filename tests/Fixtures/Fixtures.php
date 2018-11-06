<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Fixtures;

/**
 * Class Fixtures
 * @package SwipeStripe\Coupons\Tests\Fixtures
 */
final class Fixtures
{
    const FIXTURE_BASE_PATH = __DIR__;

    const ITEM_COUPONS = self::FIXTURE_BASE_PATH . '/OrderItemCoupons.yml';
    const ORDER_COUPONS = self::FIXTURE_BASE_PATH . '/OrderCoupons.yml';
    const PRODUCTS = self::FIXTURE_BASE_PATH . '/TestProducts.yml';

    /**
     * Fixtures constructor.
     */
    private function __construct()
    {
    }
}
