<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Order\OrderItem;

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
 * Class OrderItemCouponTest
 * @package SwipeStripe\Coupons\Tests\Order\OrderItem
 */
class OrderItemCouponTest extends SapphireTest
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
        Fixtures::STACKABLE_COUPONS,
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
    protected $product, $otherProduct;

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
        $coupon = OrderItemCoupon::create();
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
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNegativePercentage()
    {
        $coupon = OrderItemCoupon::create();
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
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->Amount->setValue(new Money(500, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNegativeMinQuantity()
    {
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->MinQuantity = -1;

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testDuplicateCode()
    {
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Valid';
        $coupon->Percentage = 0.1;
        $coupon->write();

        $coupon2 = OrderItemCoupon::create();
        $coupon2->Title = 'Invalid';
        $coupon2->Code = $coupon->Code;
        $coupon2->Percentage = 0.1;

        $this->expectException(ValidationException::class);
        $coupon2->write();
    }

    /**
     *
     */
    public function testDuplicateCodeFromOrderCoupon()
    {
        /** @var OrderCoupon $orderCoupon */
        $orderCoupon = OrderCoupon::get()->first();

        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Code = $orderCoupon->Code;
        $coupon->Percentage = 0.1;

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testWriteDoesntCauseDuplicateCode()
    {
        $coupon = OrderItemCoupon::get()->first();
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
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.1;
        $coupon->Code = '';

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testGetByCode()
    {
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Valid';
        $coupon->Percentage = 0.1;
        $coupon->write();

        $getByCode = OrderItemCoupon::getByCode($coupon->Code);
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
        $this->assertNull(OrderItemCoupon::getByCode('kladsfjdlasf'));
    }

    /**
     *
     */
    public function testNegativeMaxValue()
    {
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->MaxValue->setValue(new Money(-10, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testNegativeMinSubTotal()
    {
        $coupon = OrderItemCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.5;
        $coupon->MinSubTotal->setValue(new Money(-10, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     *
     */
    public function testFlatAmountFor()
    {
        /** @var OrderItemCoupon $twentyDollars */
        $twentyDollars = $this->objFromFixture(OrderItemCoupon::class, 'twenty-dollars');

        $orderItem = Order::singleton()->createCart()->getOrderItem($this->product);
        $this->assertTrue($twentyDollars->AmountFor($orderItem)->getMoney()->isZero());

        $orderItem->setQuantity(1);
        $this->assertTrue($twentyDollars->AmountFor($orderItem)->getMoney()->equals(
            new Money(-1000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $orderItem->setQuantity(2);
        $this->assertTrue($twentyDollars->AmountFor($orderItem)->getMoney()->equals(
            new Money(-2000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $orderItem->setQuantity(3);
        $this->assertTrue($twentyDollars->AmountFor($orderItem)->getMoney()->equals(
            new Money(-2000, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));
    }

    /**
     *
     */
    public function testPercentageAmountFor()
    {
        /** @var OrderItemCoupon $twentyPercent */
        $twentyPercent = $this->objFromFixture(OrderItemCoupon::class, 'twenty-percent');

        $orderItem = Order::singleton()->createCart()->getOrderItem($this->product);
        $this->assertTrue($twentyPercent->AmountFor($orderItem)->getMoney()->isZero());

        $orderItem->setQuantity(1);
        $this->assertTrue($twentyPercent->AmountFor($orderItem)->getMoney()->equals(
            new Money(-200, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $orderItem->setQuantity(2);
        $this->assertTrue($twentyPercent->AmountFor($orderItem)->getMoney()->equals(
            new Money(-400, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $orderItem->setQuantity(3);
        $this->assertTrue($twentyPercent->AmountFor($orderItem)->getMoney()->equals(
            new Money(-600, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));
    }

    /**
     *
     */
    public function testPercentageAmountForMaxValue()
    {
        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'twenty-percent-max-5');

        $orderItem = Order::singleton()->createCart()->getOrderItem($this->product);
        $this->assertTrue($coupon->AmountFor($orderItem)->getMoney()->isZero());

        $orderItem->setQuantity(1);
        $this->assertTrue($coupon->AmountFor($orderItem)->getMoney()->equals(
            new Money(-200, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $orderItem->setQuantity(2);
        $this->assertTrue($coupon->AmountFor($orderItem)->getMoney()->equals(
            new Money(-400, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));

        $orderItem->setQuantity(3);
        $this->assertTrue($coupon->AmountFor($orderItem)->getMoney()->equals(
            new Money(-500, $this->getSupportedCurrencies()->getDefaultCurrency())
        ));
    }

    /**
     *
     */
    public function testGetApplicableOrderItems()
    {
        $order = Order::singleton()->createCart();
        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'twenty-dollars');
        $this->assertCount(0, $coupon->getApplicableOrderItems($order));

        $order->addItem($this->otherProduct);
        $this->assertCount(0, $coupon->getApplicableOrderItems($order));
    }

    /**
     *
     */
    public function testGetApplicableOrderItems2()
    {
        $order = Order::singleton()->createCart();
        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'twenty-dollars');
        $this->assertCount(0, $coupon->getApplicableOrderItems($order));

        $order->addItem($this->product);
        $order->addItem($this->otherProduct);
        $this->assertCount(1, $coupon->getApplicableOrderItems($order));
    }

    /**
     *
     */
    public function testGetApplicableOrderItems3()
    {
        $order = Order::singleton()->createCart();
        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'both-products');
        $this->assertCount(0, $coupon->getApplicableOrderItems($order));

        $order->addItem($this->product);
        $order->addItem($this->otherProduct);
        $this->assertCount(2, $coupon->getApplicableOrderItems($order));
    }

    /**
     *
     */
    public function testValidForApplicableItems()
    {
        $order = Order::singleton()->createCart();
        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'twenty-percent');
        $this->assertFalse($coupon->isValidFor($order)->isValid());

        $order->addItem($this->otherProduct);
        $this->assertFalse($coupon->isValidFor($order)->isValid());

        $order->addItem($this->product);
        $this->assertTrue($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testIsApplicableFor()
    {
        /** @var OrderItemCoupon $couponOne */
        $couponOne = $this->objFromFixture(OrderItemCoupon::class, 'twenty-percent');
        /** @var OrderItemCoupon $bothProductsCoupon */
        $bothProductsCoupon = $this->objFromFixture(OrderItemCoupon::class, 'both-products');

        $this->assertTrue($couponOne->isApplicableFor($this->product));
        $this->assertTrue($bothProductsCoupon->isApplicableFor($this->product));

        $this->assertFalse($couponOne->isApplicableFor($this->otherProduct));
        $this->assertTrue($bothProductsCoupon->isApplicableFor($this->otherProduct));
    }

    /**
     *
     */
    public function testValidForValidFrom()
    {
        $order = Order::singleton()->createCart();
        $order->addItem($this->product);

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
        $order->addItem($this->product);

        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'valid-to-from');
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
        $order->addItem($this->product);

        /** @var OrderItemCoupon$coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'valid-to-from');
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
        $order->addItem($this->product);

        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'no-remaining-uses-no-limit-uses');

        $this->assertTrue($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testValidForNoRemainingUses()
    {
        $order = Order::singleton()->createCart();
        $order->addItem($this->product);

        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'no-remaining-uses');

        $this->assertFalse($coupon->isValidFor($order)->isValid());
    }

    /**
     *
     */
    public function testIsActiveForMinQuantity()
    {
        $order = Order::singleton()->createCart();
        $orderItem = $order->getOrderItem($this->product);

        /** @var OrderItemCoupon$coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'min-qty-2');
        $this->assertFalse($coupon->isActiveForItem($orderItem));

        $orderItem->setQuantity(2);
        $this->assertTrue($coupon->isActiveForItem($orderItem));
    }

    /**
     *
     */
    public function testIsActiveForMinSubTotal()
    {
        $order = Order::singleton()->createCart();
        $orderItem = $order->getOrderItem($this->product);

        /** @var OrderItemCoupon $coupon */
        $coupon = $this->objFromFixture(OrderItemCoupon::class, 'two-dollars-min-15');
        $this->assertFalse($coupon->isActiveForItem($orderItem));

        $orderItem->setQuantity(2);
        $this->assertTrue($coupon->isActiveForItem($orderItem));
    }

    /**
     *
     */
    public function testStacksWithOrderCoupon()
    {
        /** @var OrderCoupon $orderCoupon */
        $orderCoupon = $this->objFromFixture(OrderCoupon::class, 'stacks-with-item');
        /** @var OrderItemCoupon $itemCoupon */
        $itemCoupon = $this->objFromFixture(OrderItemCoupon::class, 'stacks-with-order');

        $this->assertTrue($itemCoupon->stacksWith($orderCoupon));
    }

    /**
     *
     */
    public function testStacksWithOrderItemCoupon()
    {
        /** @var OrderItemCoupon $coupon1 */
        $coupon1 = $this->objFromFixture(OrderItemCoupon::class, 'stack1');
        /** @var OrderItemCoupon $coupon2 */
        $coupon2 = $this->objFromFixture(OrderItemCoupon::class, 'stacks-with-order');

        $this->assertFalse($coupon1->stacksWith($coupon2));
        $this->assertFalse($coupon2->stacksWith($coupon1));

        $coupon2 = $this->objFromFixture(OrderItemCoupon::class, 'stack2');

        $this->assertTrue($coupon1->stacksWith($coupon2));
        $this->assertTrue($coupon2->stacksWith($coupon1));
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->product = $this->objFromFixture(TestProduct::class, 'product');
        $this->otherProduct = $this->objFromFixture(TestProduct::class, 'other-product');
    }
}
