<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SwipeStripe\Coupons\Order\OrderCoupon;
use SwipeStripe\Coupons\Order\OrderExtension;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemCoupon;
use SwipeStripe\Coupons\Order\OrderItem\OrderItemExtension;
use SwipeStripe\Order\Checkout\CheckoutFormInterface;
use SwipeStripe\Order\Checkout\CheckoutFormRequestHandler;
use SwipeStripe\Order\Order;
use SwipeStripe\Order\OrderItem\OrderItem;

/**
 * Class CheckoutFormRequestHandlerExtension
 * @package SwipeStripe\Shipping\Checkout
 * @property CheckoutFormRequestHandler|CheckoutFormRequestHandlerExtension $owner
 */
class CheckoutFormRequestHandlerExtension extends Extension
{
    /**
     * @param array $data
     * @param CheckoutFormInterface $form
     * @return HTTPResponse
     */
    public function ApplyCoupon(array $data, CheckoutFormInterface $form): HTTPResponse
    {
        $code = $data[CheckoutFormExtension::COUPON_CODE_FIELD];
        $coupon = !empty($code)
            ? OrderCoupon::getByCode($code) ?? OrderItemCoupon::getByCode($code)
            : null;

        if ($coupon === null) {
            throw ValidationException::create(ValidationResult::create()
                ->addFieldError(CheckoutFormExtension::COUPON_CODE_FIELD,
                    _t(self::class . '.COUPON_INVALID', 'Sorry, that coupon code is invalid.')));
        }

        $couponValid = $coupon->isValidFor($form->getCart());
        if (!$couponValid->isValid()) {
            throw ValidationException::create($couponValid);
        }

        $order = $this->getMutableCart($form);
        $order->clearAppliedOrderCoupons();
        $order->clearAppliedOrderItemCoupons();

        if ($coupon instanceof OrderCoupon) {
            $order->applyCoupon($coupon);
        } else {
            /** @var OrderItem|OrderItemExtension $orderItem */
            foreach ($coupon->getApplicableOrderItems($order) as $orderItem) {
                $orderItem->applyCoupon($coupon);
            }
        }

        return $this->owner->redirectBack();
    }

    /**
     * @param CheckoutFormInterface $form
     * @return Order|OrderExtension
     */
    protected function getMutableCart(CheckoutFormInterface $form): Order
    {
        $cart = $form->getCart();
        if ($cart->IsMutable()) {
            return $cart;
        }

        $clone = $cart->duplicate();
        $clone->Unlock();
        $form->setCart($clone);

        if ($cart->ID === $this->owner->ActiveCart->ID) {
            $this->owner->setActiveCart($clone);
        }

        return $clone;
    }

    /**
     * @param array $data
     * @param CheckoutFormInterface $form
     * @return HTTPResponse
     */
    public function RemoveAppliedCoupons(array $data, CheckoutFormInterface $form): HTTPResponse
    {
        $order = $this->getMutableCart($form);
        $order->clearAppliedOrderCoupons();
        $order->clearAppliedOrderItemCoupons();

        return $this->owner->redirectBack();
    }
}
