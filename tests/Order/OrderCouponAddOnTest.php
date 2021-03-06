<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order;

use SilverStripe\Dev\SapphireTest;
use SwipeStripe\Coupons\Order\OrderCoupon;
use SwipeStripe\Coupons\Order\OrderCouponAddOn;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\Tests\Fixtures\Fixtures;
use SwipeStripe\Coupons\Tests\NeedsSupportedCurrencies;
use SwipeStripe\Coupons\Tests\TestProduct;
use SwipeStripe\Order\Order;

/**
 * Class OrderCouponAddOnTest
 * @package SwipeStripe\Coupons\Tests\Order
 */
class OrderCouponAddOnTest extends SapphireTest
{
    use NeedsSupportedCurrencies;

    /**
     * @var array
     */
    protected static $fixture_file = [
        Fixtures::ORDER_COUPONS,
        Fixtures::PRODUCTS,
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
     * @var TestProduct
     */
    protected $product;

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
        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'twenty-dollars');

        $addOn = OrderCouponAddOn::create();
        $addOn->OrderID = $order->ID;
        $addOn->setCoupon($coupon)
            ->write();

        $this->assertSame($coupon->ID, $addOn->Coupon()->ID);
        $this->assertTrue($addOn->Amount->getMoney()->equals(
            $coupon->AmountFor($order)->getMoney()
        ));

        $order->setItemQuantity($this->product, 3);
        $this->assertTrue($addOn->Amount->getMoney()->equals(
            $coupon->AmountFor($order)->getMoney()
        ));
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->product = $this->objFromFixture(TestProduct::class, 'product');
    }
}
