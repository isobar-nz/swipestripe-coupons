<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order;

use SilverStripe\Dev\SapphireTest;
use SwipeStripe\Coupons\Order\OrderCouponAddOn;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\Tests\NeedsSupportedCurrencies;
use SwipeStripe\Order\Order;

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
    public function testGetCouponAddOn()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();

        $this->assertNull($order->OrderAddOns()->find('ClassName', OrderCouponAddOn::class));
        $this->assertInstanceOf(OrderCouponAddOn::class, $order->getCouponAddOn());
    }

    /**
     *
     */
    public function testGetCouponAddOnExisting()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();

        $addOn = OrderCouponAddOn::create();
        $addOn->OrderID = $order->ID;
        $addOn->write();

        $getAddOn = $order->getCouponAddOn();
        $this->assertInstanceOf(OrderCouponAddOn::class, $getAddOn);
        $this->assertSame($addOn->ID, $getAddOn->ID);
    }

    /**
     *
     */
    public function testHasCoupons()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();

        $this->assertFalse($order->hasCoupons());

        $addOn = $order->getCouponAddOn();
        $this->assertFalse($order->hasCoupons());

        $addOn->write();
        $this->assertTrue($order->hasCoupons());
    }
}
