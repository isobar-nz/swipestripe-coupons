<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order\OrderItem;

use SilverStripe\Dev\SapphireTest;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCouponAddOn;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemExtension;
use SwipeStripe\Coupons\Tests\Fixtures\Fixtures;
use SwipeStripe\Coupons\Tests\NeedsSupportedCurrencies;
use SwipeStripe\Coupons\Tests\TestProduct;
use SwipeStripe\Order\Order;
use SwipeStripe\Order\OrderItem\OrderItem;

class OrderItemExtensionTest extends SapphireTest
{
    use NeedsSupportedCurrencies;

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TestProduct::class,
    ];

    /**
     * @var array
     */
    protected static $fixture_file = [
        Fixtures::PRODUCTS,
        Fixtures::ITEM_COUPONS,
        Fixtures::ORDER_COUPONS,
    ];

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
    public function testApplyCoupon()
    {
        /** @var Order $order */
        $order = Order::singleton()->createCart();
        /** @var TestProduct $product */
        $product = $this->objFromFixture(TestProduct::class, 'product');
        $order->addItem($product);

        /** @var OrderItem|OrderItemExtension $orderItem */
        $orderItem = $order->getOrderItem($product);

        $this->assertCount(0, $orderItem->OrderItemAddOns());

        /** @var OrderItemCoupon $coupon */
        $coupon = OrderItemCoupon::get()->first();
        $orderItem->applyCoupon($coupon);

        $this->assertCount(1, $orderItem->OrderItemAddOns());

        /** @var OrderItemCouponAddOn $addOn */
        $addOn = $orderItem->OrderItemAddOns()->first();
        $this->assertInstanceOf(get_class($coupon), $addOn->Coupon());
        $this->assertSame($coupon->ID, $addOn->Coupon()->ID);
    }
}
