<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SwipeStripe\Order\OrderAddOn;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderCouponAddOn
 * @package SwipeStripe\Coupons\Order
 * @property int $OrderCouponID
 * @method OrderCoupon OrderCoupon()
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
    private static $has_one = [
        'OrderCoupon' => OrderCoupon::class,
    ];

    /**
     * @param OrderCoupon $coupon
     * @return $this
     */
    public function setOrderCoupon(OrderCoupon $coupon): self
    {
        $this->OrderCouponID = $coupon->ID;
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
        return $this->OrderCoupon()->AmountFor($this->Order());
    }
}
