<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponCMSPermissions;
use SwipeStripe\Coupons\SelfStackThroughObject;

/**
 * Class OrderItemCouponStackThrough
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property int $OrderItemCoupon1ID
 * @property int $OrderItemCoupon2ID
 * @property OrderItemCoupon OrderItemCoupon1()
 * @property OrderItemCoupon OrderItemCoupon2()
 * @mixin Versioned
 */
class OrderItemCouponStackThrough extends DataObject
{
    use CouponCMSPermissions;
    use SelfStackThroughObject;

    const LEFT = 'OrderItemCoupon1';
    const RIGHT = 'OrderItemCoupon2';

    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderItemCoupon_ItemCouponStacks';

    /**
     * @var array
     */
    private static $has_one = [
        self::LEFT  => OrderItemCoupon::class,
        self::RIGHT => OrderItemCoupon::class,
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class => Versioned::class . '.versioned',
    ];
}
