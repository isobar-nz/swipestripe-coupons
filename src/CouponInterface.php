<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SwipeStripe\Order\Order;

/**
 * Interface CouponInterface
 * @package SwipeStripe\Coupons
 * @mixin DataObject
 */
interface  CouponInterface
{
    /**
     * @param string $code
     * @return null|static
     */
    public static function getByCode(string $code): ?self;

    /**
     * @param Order $order
     * @param string $fieldName
     * @return ValidationResult
     */
    public function isValidFor(Order $order, string $fieldName = 'Coupon'): ValidationResult;

    /**
     * @param CouponInterface $other
     * @return bool
     */
    public function stacksWith(CouponInterface $other): bool;
}
