<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\ORM\DataExtension;
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
        return OrderCouponAddOn::get()->find('OrderID', $this->owner->ID) !== null ||
            OrderItemCouponAddOn::get()->find('OrderItem.OrderID', $this->owner->ID) !== null;
    }

    /**
     * @return OrderCouponAddOn
     */
    public function getCouponAddOn(): OrderCouponAddOn
    {
        /** @var OrderCouponAddOn|null $existing */
        $existing = OrderCouponAddOn::get()->find('OrderID', $this->owner->ID);
        if ($existing !== null) {
            return $existing;
        }

        $new = OrderCouponAddOn::create();
        $new->OrderID = $this->owner->ID;

        return $new;
    }

    /**
     * @param bool $includeOrderItems
     * @return int
     */
    public function clearAppliedCoupons(bool $includeOrderItems = true): int
    {
        $count = 0;

        /** @var OrderCouponAddOn $addOn */
        foreach (OrderCouponAddOn::get()->filter('OrderID', $this->owner->ID) as $addOn) {
            $addOn->delete();
            $count++;
        }

        if ($includeOrderItems) {
            /** @var OrderItemCouponAddOn $addOn */
            foreach (OrderItemCouponAddOn::get()->filter('OrderItem.OrderID', $this->owner->ID) as $addOn) {
                $addOn->delete();
                $count++;
            }
        }

        return $count;
    }
}
