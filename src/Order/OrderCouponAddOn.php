<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SwipeStripe\Order\OrderAddOn;
use SwipeStripe\Order\OrderLockedException;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderCouponAddOn
 * @package SwipeStripe\Coupons\Order
 * @property bool $UseRecorded
 * @property int $CouponID
 * @method OrderCoupon Coupon()
 */
class OrderCouponAddOn extends OrderAddOn
{
    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderAddOn';

    /**
     * @var array
     */
    private static $db = [
        'UseRecorded' => 'Boolean',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Coupon' => OrderCoupon::class,
    ];

    /**
     * @param OrderCoupon $coupon
     * @return $this
     */
    public function setCoupon(OrderCoupon $coupon): self
    {
        if (!$this->Order()->IsMutable()) {
            throw new OrderLockedException($this->Order());
        }

        $this->CouponID = $coupon->ID;
        $this->Title = _t(self::class . '.ADDON_TITLE', 'Coupon - {coupon_title}', [
            'coupon_title' => $coupon->Title,
        ]);

        return $this;
    }

    /**
     * @return DBPrice
     */
    public function getAmount(): DBPrice
    {
        return $this->Coupon()->AmountFor($this->Order());
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return parent::isActive() && $this->Coupon()->isValidFor($this->Order()) && $this->Amount->hasAmount()
            && (!$this->Coupon()->LimitUses || $this->Coupon()->RemainingUses > 0);
    }
}
