<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\ORM\DataExtension;
use SwipeStripe\Order\OrderItem\OrderItem;

/**
 * Class OrderItemExtension
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property OrderItem|OrderItemExtension $owner
 */
class OrderItemExtension extends DataExtension
{
    /**
     * @param OrderItemCoupon $coupon
     */
    public function applyCoupon(OrderItemCoupon $coupon): void
    {
        $existing = OrderItemCouponAddOn::get()->filter([
            'OrderItemID' => $this->owner->ID,
            'CouponID'    => $coupon->ID,
        ])->exists();

        if (!$existing) {
            $addOn = OrderItemCouponAddOn::create();
            $addOn->OrderItemID = $this->owner->ID;
            $addOn->setCoupon($coupon);
            $addOn->write();
        }
    }
}
