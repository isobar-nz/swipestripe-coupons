<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\ORM\DataExtension;
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
        return $this->getCouponAddOn()->exists();
    }

    /**
     * @return OrderCouponAddOn
     */
    public function getCouponAddOn(): OrderCouponAddOn
    {
        /** @var OrderCouponAddOn|null $existing */
        $existing = OrderCouponAddOn::get_one(OrderCouponAddOn::class, ['OrderID' => $this->owner->ID]);
        if ($existing !== null) {
            return $existing;
        }

        $new = OrderCouponAddOn::create();
        $new->OrderID = $this->owner->ID;
        $new->flushCache();

        return $new;
    }
}
