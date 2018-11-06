<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponCMSPermissions;
use SwipeStripe\Order\PurchasableInterface;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderItemCouponPurchasable
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property int $OrderItemCouponID
 * @method OrderItemCoupon OrderItemCoupon()
 * @property int $PurchasableID
 * @property string $PurchasableClass
 * @property DBPrice $CouponValue
 * @method PurchasableInterface|DataObject Purchasable()
 * @mixin Versioned
 */
class OrderItemCouponPurchasable extends DataObject
{
    use CouponCMSPermissions;

    const ORDER_ITEM_COUPON = 'OrderItemCoupon';
    const PURCHASABLE = 'Purchasable';

    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderItemCoupon_Purchasable';
    /**
     * @var array
     */
    private static $has_one = [
        self::ORDER_ITEM_COUPON => OrderItemCoupon::class,
        self::PURCHASABLE       => DataObject::class,
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class => Versioned::class . '.versioned',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        self::PURCHASABLE . '.Title' => 'Product',
        self::PURCHASABLE . '.Price' => 'Price',
    ];
}
