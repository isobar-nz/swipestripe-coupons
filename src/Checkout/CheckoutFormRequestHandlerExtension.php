<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\OrderCoupon;
use SwipeStripe\Order\Checkout\CheckoutForm;
use SwipeStripe\Order\Checkout\CheckoutFormRequestHandler;
use SwipeStripe\Order\Order;

/**
 * Class CheckoutFormRequestHandlerExtension
 * @package SwipeStripe\Shipping\Checkout
 * @property CheckoutFormRequestHandler|CheckoutFormRequestHandlerExtension $owner
 */
class CheckoutFormRequestHandlerExtension extends Extension
{
    /**
     * @param array $data
     * @param CheckoutForm|CheckoutFormExtension $form
     * @return HTTPResponse
     */
    public function ApplyCoupon(array $data, CheckoutForm $form): HTTPResponse
    {
        $code = $data[CheckoutFormExtension::COUPON_CODE_FIELD];

        if (!empty($code)) {
            /** @var Order|OrderExtension $order */
            $order = $form->getCart();
            $order->getCouponAddOn()
                ->setOrderCoupon(OrderCoupon::getByCode($code))
                ->write();
        }

        return $this->owner->redirectBack();
    }

    /**
     * @param array $data
     * @param CheckoutForm $form
     * @return HTTPResponse
     */
    public function RemoveAppliedCoupons(array $data, CheckoutForm $form): HTTPResponse
    {
        /** @var Order|OrderExtension $order */
        $order = $form->getCart();
        $couponAddOn = $order->getCouponAddOn();

        if ($couponAddOn->exists()) {
            $couponAddOn->delete();
        }

        return $this->owner->redirectBack();
    }
}
