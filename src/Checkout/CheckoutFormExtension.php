<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Order\Checkout\CheckoutFormInterface;
use SwipeStripe\Order\Order;

/**
 * Class CheckoutFormExtension
 * @package SwipeStripe\Coupons\Checkout
 * @property CheckoutFormInterface|CheckoutFormExtension $owner
 */
class CheckoutFormExtension extends Extension
{
    const COUPON_CODE_FIELD = 'CouponCode';

    /**
     * @param FieldList $fields
     */
    public function updateFields(FieldList $fields): void
    {
        $fields->add(TextField::create(static::COUPON_CODE_FIELD,
            _t(self::class . '.COUPON_CODE', 'Coupon Code')));
    }

    /**
     * @param FieldList $actions
     */
    public function updateActions(FieldList $actions): void
    {
        /** @var Order|OrderExtension $cart */
        $cart = $this->owner->getCart();

        if ($cart->hasCoupons()) {
            $actions->unshift(
                FormAction::create('RemoveAppliedCoupons',
                    _t(self::class . '.REMOVE_APPLIED_COUPONS', 'Remove Applied Coupon(s)'))
                    ->setValidationExempt()
            );
        }

        $actions->unshift(FormAction::create('ApplyCoupon',
            _t(self::class . '.APPLY_COUPON', 'Apply Coupon')));
    }
}
