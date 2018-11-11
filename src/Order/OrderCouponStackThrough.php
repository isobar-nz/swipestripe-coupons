<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponCMSPermissions;
use SwipeStripe\Coupons\SelfStackThroughObject;

/**
 * Class OrderCouponStackThrough
 * @package SwipeStripe\Coupons\Order
 * @property int $OrderCoupon1ID
 * @property int $OrderCoupon2ID
 * @property OrderCoupon OrderCoupon1()
 * @property OrderCoupon OrderCoupon2()
 * @mixin Versioned
 */
class OrderCouponStackThrough extends DataObject
{
    use CouponCMSPermissions;
    use SelfStackThroughObject;

    const LEFT = 'OrderCoupon1';
    const RIGHT = 'OrderCoupon2';

    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderCoupon_OrderCouponStacks';

    /**
     * @var array
     */
    private static $has_one = [
        self::LEFT  => OrderCoupon::class,
        self::RIGHT => OrderCoupon::class,
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class => Versioned::class . '.versioned',
    ];
}
