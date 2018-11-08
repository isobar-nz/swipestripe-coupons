<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order;

use Money\Money;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationException;
use SwipeStripe\Coupons\Order\OrderCoupon;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;
use SwipeStripe\Coupons\Tests\Fixtures\Fixtures;
use SwipeStripe\Coupons\Tests\NeedsSupportedCurrencies;
use SwipeStripe\Coupons\Tests\TestProduct;
use SwipeStripe\Coupons\Tests\WaitsMockTime;
use SwipeStripe\Order\Order;

/**
 * Class OrderCouponTest
 * @package SwipeStripe\Coupons\Tests\Order
 */
class OrderCouponTest extends SapphireTest
{
    use NeedsSupportedCurrencies;
    use WaitsMockTime;

    /**
     * @var array
     */
    protected static $fixture_file = [
        Fixtures::PRODUCTS,
        Fixtures::ITEM_COUPONS,
        Fixtures::ORDER_COUPONS,
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
    public function testNegativeAmount()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Amount->setValue(new Money(-1, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNoAmountOrPercentage()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNegativePercentage()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = -0.5;

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testAmountAndPercentageSet()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->Amount->setValue(new Money(500, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testDuplicateCode()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Valid';
        $coupon->Percentage = 0.1;
        $coupon->write();

        $coupon2 = OrderCoupon::create();
        $coupon2->Title = 'Invalid';
        $coupon2->Code = $coupon->Code;
        $coupon2->Percentage = 0.1;

        $this->expectException(ValidationException::class);
        $coupon2->write();
    }

    /**
     *
     */
    public function testDuplicateCodeFromOrderItemCoupon()
    {
        /** @var OrderItemCoupon $orderItemCoupon */
        $orderItemCoupon = OrderItemCoupon::get()->first();

        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Code = $orderItemCoupon->Code;
        $coupon->Percentage = 0.1;

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testWriteDoesntCauseDuplicateCode()
    {
        $coupon = OrderCoupon::get()->first();
        $originalTitle = $coupon->Title;
        $coupon->Title = 'Rename test';
        $coupon->write();

        $coupon->Title = $originalTitle;
        $coupon->write();
    }

    /**
     *
     */
    public function testEmptyCode()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.1;
        $coupon->Code = '';

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNegativeMinSubTotal()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->MinSubTotal->setValue(new Money(-10, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNegativeMaxValue()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->MaxValue->setValue(new Money(-10, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testGetByCode()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Valid';
        $coupon->Percentage = 0.1;
        $coupon->write();

        $getByCode = OrderCoupon::getByCode($coupon->Code);
        $this->assertSame($coupon->ID, $getByCode->ID);
        $this->assertSame($coupon->Title, $getByCode->Title);
        $this->assertSame($coupon->Code, $getByCode->Code);
        $this->assertEquals($coupon->Percentage, $getByCode->Percentage);
    }

    /**
     *
     */
    public function testGetByInvalidCode()
    {
        $this->assertNull(OrderCoupon::getByCode('kladsfjdlasf'));
    }

    /**
     *
     */
    public function testFlatAmountFor()
    {
        /** @var OrderCoupon $twentyDollars */
        $twentyDollars = $this->objFromFixture(OrderCoupon::class, 'twenty-dollars');

        $order = Order::singleton()->createCart();
        $this->assertTrue($twentyDollars->AmountFor($order)->getMoney()->isZero());

        $order->setItemQuantity($this->product, 1);
        $this->assertTrue($twentyDollars->AmountFor($order)->getMoney()->equals(
            new Money(-1000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $order->setItemQuantity($this->product, 2);
        $this->assertTrue($twentyDollars->AmountFor($order)->getMoney()->equals(
            new Money(-2000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $order->setItemQuantity($this->product, 3);
        $this->assertTrue($twentyDollars->AmountFor($order)->getMoney()->equals(
            new Money(-2000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));
    }

    /**
     *
     */
    public function testPercentageAmountFor()
    {
        /** @var OrderCoupon $twentyPercent */
        $twentyPercent = $this->objFromFixture(OrderCoupon::class, 'twenty-percent');

        $order = Order::singleton()->createCart();
        $this->assertTrue($twentyPercent->AmountFor($order)->getMoney()->isZero());

        $order->setItemQuantity($this->product, 1);
        $this->assertTrue($twentyPercent->AmountFor($order)->getMoney()->equals(
            new Money(-200, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $order->setItemQuantity($this->product, 2);
        $this->assertTrue($twentyPercent->AmountFor($order)->getMoney()->equals(
            new Money(-400, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $order->setItemQuantity($this->product, 3);
        $this->assertTrue($twentyPercent->AmountFor($order)->getMoney()->equals(
            new Money(-600, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));
    }

    /**
     *
     */
    public function testIsValidForMinTotal()
    {
        $order = Order::singleton()->createCart();

        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'min-ten-dollars');
        $this->assertFalse($coupon->isValidFor($order)->isValid());

        $order->setItemQuantity($this->product, 1);
        $this->assertTrue($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testValidForValidFrom()
    {
        $order = Order::singleton()->createCart();

        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'valid-to-from');
        $coupon->ValidFrom = DBDatetime::now()->getTimestamp() + 30;
        $coupon->ValidUntil = null;

        $this->assertFalse($coupon->isValidFor($order)->isValid());

        $this->mockWait(60);
        $this->assertTrue($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testValidForValidUntil()
    {
        $order = Order::singleton()->createCart();

        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'valid-to-from');
        $coupon->ValidFrom = null;
        $coupon->ValidUntil = DBDatetime::now()->getTimestamp() + 30;

        $this->assertTrue($coupon->isValidFor($order)->isValid());

        $this->mockWait(60);
        $this->assertFalse($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testValidForValidFromUntil()
    {
        $order = Order::singleton()->createCart();

        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'valid-to-from');
        $coupon->ValidFrom = DBDatetime::now()->getTimestamp() + 30;
        $coupon->ValidUntil = DBDatetime::now()->getTimestamp() + 60;

        $this->assertFalse($coupon->isValidFor($order)->isValid());

        $this->mockWait(45);
        $this->assertTrue($coupon->isValidFor($order)->isValid());

        $this->mockWait(45);
        $this->assertFalse($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testValidForNoLimitUsesNoRemainingUses()
    {
        $order = Order::singleton()->createCart();
        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'no-remaining-uses-no-limit-uses');

        $this->assertTrue($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testValidForNoRemainingUses()
    {
        $order = Order::singleton()->createCart();
        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'no-remaining-uses');

        $this->assertFalse($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testAmountForMaxValue()
    {
        $order = Order::singleton()->createCart();

        /** @var OrderCoupon $coupon */
        $coupon = $this->objFromFixture(OrderCoupon::class, 'fifty-percent-max-10');
        $this->assertTrue($coupon->AmountFor($order)->getMoney()->isZero());

        $order->setItemQuantity($this->product, 1);
        $this->assertTrue($coupon->AmountFor($order)->getMoney()->equals(
            new Money(-500, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $order->setItemQuantity($this->product, 2);
        $this->assertTrue($coupon->AmountFor($order)->getMoney()->equals(
            new Money(-1000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $order->setItemQuantity($this->product, 3);
        $this->assertTrue($coupon->AmountFor($order)->getMoney()->equals(
            new Money(-1000, $this->getSupportedCurrencies()->getDefaultCurrency())
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
