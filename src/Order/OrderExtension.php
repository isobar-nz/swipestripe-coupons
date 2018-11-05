<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCouponAddOn;
use SwipeStripe\Order\Order;

/**
 * Class OrderExtension
 * @package SwipeStripe\Coupons\Order
 * @property Order|OrderExtension $owner
 */
class OrderExtension extends DataExtension
{
    /**
     * @return bool
     */
    public function hasCoupons(): bool
    {
        return $this->owner->OrderCouponAddOns()->exists() || $this->owner->OrderItemCouponAddOns()->exists();
    }

    /**
     * @return DataList|OrderCouponAddOn[]
     */
    public function OrderCouponAddOns(): DataList
    {
        return OrderCouponAddOn::get()->filter('OrderID', $this->owner->ID);
    }

    /**
     * @return DataList|OrderItemCouponAddOn[]
     */
    public function OrderItemCouponAddOns(): DataList
    {
        return OrderItemCouponAddOn::get()->filter('OrderItem.OrderID', $this->owner->ID);
    }

    /**
     * @param OrderCoupon $coupon
     */
    public function applyCoupon(OrderCoupon $coupon): void
    {
        if ($this->owner->OrderCouponAddOns()->find('CouponID', $coupon->ID) === null) {
            $addOn = OrderCouponAddOn::create();
            $addOn->OrderID = $this->owner->ID;
            $addOn->setCoupon($coupon);
            $addOn->write();
        }
    }

    /**
     * @return int
     */
    public function clearAppliedOrderCoupons(): int
    {
        $count = 0;

        foreach ($this->owner->OrderCouponAddOns() as $addOn) {
            $addOn->delete();
            $count++;
        }

        return $count;
    }

    /**
     * @return int
     */
    public function clearAppliedOrderItemCoupons(): int
    {
        $count = 0;

        foreach ($this->owner->OrderItemCouponAddOns() as $addOn) {
            $addOn->delete();
            $count++;
        }

        return $count;
    }
}
