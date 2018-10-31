<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order;

use SilverStripe\Dev\SapphireTest;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\OrderCoupon;
use SwipeStripe\Coupons\Tests\Fixtures\Fixtures;
use SwipeStripe\Coupons\Tests\Fixtures\PublishesFixtures;
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
    use PublishesFixtures;

    /**
     * @var array
     */
    protected static $fixture_file = [
        Fixtures::COUPONS,
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
    public function testSetOrderCoupon()
    {
        /** @var Order|OrderExtension $order */
        $order = Order::singleton()->createCart();
        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'twenty-dollars');

        $addOn = $order->getCouponAddOn();
        $addOn->setOrderCoupon($coupon)
            ->write();

        $this->assertSame($coupon->ID, $addOn->OrderCoupon()->ID);
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
        $this->registerPublishingBlueprint(OrderCoupon::class);
        $this->registerPublishingBlueprint(TestProduct::class);

        parent::setUp();

        $this->product = $this->objFromFixture(TestProduct::class, 'product');
    }
}
