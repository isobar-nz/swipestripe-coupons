<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Core\Extension;
use SwipeStripe\Coupons\Order\OrderCoupon;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;
use SwipeStripe\Order\Checkout\CheckoutForm;
use SwipeStripe\Order\Checkout\CheckoutFormValidator;
use SwipeStripe\Order\Order;

/**
 * Class CheckoutFormValidatorExtension
 * @package SwipeStripe\Coupons\Checkout
 * @property CheckoutFormValidator|CheckoutFormValidatorExtension $owner
 */
class CheckoutFormValidatorExtension extends Extension
{
    /**
     * @param CheckoutForm $form
     * @param array $data
     */
    public function validate(CheckoutForm $form, array $data): void
    {
        $this->owner->validateAppliedCoupons($form->getCart());

        $code = $data[CheckoutFormExtension::COUPON_CODE_FIELD];
        if (empty($code)) {
            return;
        }

        $coupon = OrderCoupon::getByCode($code) ?? OrderItemCoupon::getByCode($code);
        if ($coupon === null) {
            $this->owner->validationError(CheckoutFormExtension::COUPON_CODE_FIELD,
                _t(self::class . '.COUPON_INVALID', 'Sorry, that coupon code is invalid.'));
        } else {
            $this->owner->getResult()->combineAnd(
                $coupon->isValidFor($form->getCart(), CheckoutFormExtension::COUPON_CODE_FIELD)
            );
        }
    }

    /**
     * @param Order|OrderExtension $cart
     */
    public function validateAppliedCoupons(Order $cart): void
    {
        $result = $this->owner->getResult();

        foreach ($cart->OrderCouponAddOns() as $addOn) {
            $result->combineAnd($addOn->Coupon()->isValidFor($cart));
        }
    }
}
