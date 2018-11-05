<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order\OrderItem;

use SilverStripe\Dev\SapphireTest;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCouponAddOn;
use SwipeStripe\Coupons\Tests\Fixtures\Fixtures;
use SwipeStripe\Coupons\Tests\NeedsSupportedCurrencies;
use SwipeStripe\Coupons\Tests\TestProduct;
use SwipeStripe\Order\Order;

/**
 * Class OrderItemCouponAddOnTest
 * @package SwipeStripe\Coupons\Tests\Order\OrderItem
 */
class OrderItemCouponAddOnTest extends SapphireTest
{
    use NeedsSupportedCurrencies;

    /**
     * @var array
     */
    protected static $fixture_file = [
        Fixtures::PRODUCTS,
        Fixtures::ITEM_COUPONS,
    ];

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TestProduct::class,
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
    public function testSetCoupon()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();
        /** @var TestProduct $product */
        $product = $this->objFromFixture(TestProduct::class, 'product');
        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'twenty-dollars');

        $order->addItem($product);
        $orderItem = $order->getOrderItem($product);

        $addOn = OrderItemCouponAddOn::create();
        $addOn->OrderItemID = $orderItem->ID;
        $addOn->setCoupon($coupon)
            ->write();

        $this->assertSame($coupon->ID, $addOn->Coupon()->ID);
        $this->assertTrue($addOn->Amount->getMoney()->equals(
            $coupon->AmountFor($orderItem)->getMoney()
        ));

        $order->setItemQuantity($product, 3);
        $this->assertTrue($addOn->Amount->getMoney()->equals(
            $coupon->AmountFor($orderItem)->getMoney()
        ));
    }
}
