<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests;

use Money\Money;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ValidationException;
use SwipeStripe\Coupons\OrderCoupon;
use SwipeStripe\Coupons\Tests\Fixtures\Fixtures;
use SwipeStripe\Coupons\Tests\Fixtures\PublishesFixtures;
use SwipeStripe\Order\Order;

/**
 * Class OrderCouponTest
 * @package SwipeStripe\Coupons\Tests
 */
class OrderCouponTest extends SapphireTest
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
    public function testNegativeAmount()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Code = $this->generateCode();
        $coupon->Amount->setValue(new Money(-1, $this->getSupportedCurrencies()->getDefaultCurrency()));

        $this->expectException(ValidationException::class);
        $coupon->write();
    }

    /**
     * @param int $length
     * @return string
     */
    protected function generateCode(int $length = 8): string
    {
        $bytes = intval(ceil($length / 2));

        try {
            $random = bin2hex(random_bytes($bytes));
            return substr($random, 0, $length);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     *
     */
    public function testNoAmountOrPercentage()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Code = $this->generateCode();

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
        $coupon->Code = $this->generateCode();
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
        $coupon->Code = $this->generateCode();
        $coupon->Percentage = -0.5;
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
        $coupon->Code = $this->generateCode();
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
    public function testEmptyCode()
    {
        $coupon = OrderCoupon::create();
        $coupon->Title = 'Invalid';
        $coupon->Percentage = 0.1;

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
        $coupon->Code = $this->generateCode();
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
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->registerPublishingBlueprint(TestProduct::class);
        $this->registerPublishingBlueprint(OrderCoupon::class);

        parent::setUp();

        $this->product = $this->objFromFixture(TestProduct::class, 'product');
    }
}
