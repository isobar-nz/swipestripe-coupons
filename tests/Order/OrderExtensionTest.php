<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order;

use SilverStripe\Dev\SapphireTest;
use SwipeStripe\Coupons\Order\OrderCouponAddOn;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\Tests\NeedsSupportedCurrencies;
use SwipeStripe\Order\Order;
use SwipeStripe\Order\OrderAddOn;

/**
 * Class OrderExtensionTest
 * @package SwipeStripe\Coupons\Tests\Order
 */
class OrderExtensionTest extends SapphireTest
{
    use NeedsSupportedCurrencies;

    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::setupSupportedCurrencies();
    }

    /**
     *
     */
    public function testHasCoupons()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();
        $this->assertFalse($order->hasCoupons());

        $addOn = OrderCouponAddOn::create();
        $addOn->OrderID = $order->ID;
        $addOn->write();
        $this->assertTrue($order->hasCoupons());
    }

    /**
     *
     */
    public function testHasCouponsDifferentAddOn()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();
        $this->assertFalse($order->hasCoupons());

        $addOn = OrderAddOn::create();
        $addOn->OrderID = $order->ID;
        $addOn->write();
        $this->assertFalse($order->hasCoupons());
    }
}
