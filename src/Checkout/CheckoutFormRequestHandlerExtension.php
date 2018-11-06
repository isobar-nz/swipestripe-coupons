<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Checkout;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
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
        if (empty($code)) {
            return $this->owner->redirectBack();
        }

        $orderCoupon = OrderCoupon::getByCode($code);
        if ($orderCoupon !== null) {
            $order = $this->getMutableCart($form);
            $order->clearAppliedOrderCoupons();
            $order->clearAppliedOrderItemCoupons();
            $order->applyCoupon($orderCoupon);
        } else {
            $orderItemCoupon = OrderItemCoupon::getByCode($code);
            if ($orderItemCoupon !== null) {
                $order = $this->getMutableCart($form);
                $order->clearAppliedOrderCoupons();
                $order->clearAppliedOrderItemCoupons();
                /** @var OrderItem|OrderItemExtension $orderItem */
                foreach ($orderItemCoupon->getApplicableOrderItems($order) as $orderItem) {
                    $orderItem->applyCoupon($orderItemCoupon);
                }
            }
        }

        return $this->owner->redirectBack();
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
}
