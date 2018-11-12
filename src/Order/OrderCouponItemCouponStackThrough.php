<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponCMSPermissions;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;

/**
 * Class OrderCouponItemCouponStackThrough
 * @package SwipeStripe\Coupons\Order
 * @property int $OrderCouponID
 * @property int $OrderItemCouponID
 * @property OrderCoupon OrderCoupon()
 * @property OrderItemCoupon OrderItemCoupon()
 * @mixin Versioned
 */
class OrderCouponItemCouponStackThrough extends DataObject
{
    use CouponCMSPermissions;

    const ORDER_COUPON = 'OrderCoupon';
    const ORDER_ITEM_COUPON = 'OrderItemCoupon';

    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderCoupon_OrderItemCouponStacks';

    /**
     * @var array
     */
    private static $has_one = [
        self::ORDER_COUPON      => OrderCoupon::class,
        self::ORDER_ITEM_COUPON => OrderItemCoupon::class,
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class => Versioned::class . '.versioned',
    ];
}
