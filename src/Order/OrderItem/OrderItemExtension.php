<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\ORM\DataExtension;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Order\Order;
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

    /**
     * Copy any already applied coupons to new applicable order items.
     */
    public function onAfterWrite()
    {
        if (!$this->owner->IsMutable() || !$this->owner->isChanged('ID')) {
            // Only run on first write, only run if mutable
            return;
        }

        /** @var Order|OrderExtension $order */
        $order = $this->owner->Order();
        $orderItemCouponIds = $order->OrderItemCouponAddOns()->column('CouponID');

        if (count($orderItemCouponIds) > 0) {
            $purchasable = $this->owner->Purchasable();

            /** @var OrderItemCoupon $coupon */
            foreach (OrderItemCoupon::get()->byIDs($orderItemCouponIds) as $coupon) {
                if ($coupon->isApplicableFor($purchasable)) {
                    $this->owner->applyCoupon($coupon);
                }
            }
        }
    }
}
