<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyThroughList;
use SwipeStripe\Order\PurchasableInterface;

/**
 * Class CouponAware
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property PurchasableInterface|DataObject|CouponAware $owner
 * @method ManyManyThroughList|OrderItemCoupon[] OrderItemCoupons()
 */
class CouponAware extends DataExtension
{
    /**
     * @var array
     */
    private static $many_many = [
        'OrderItemCoupons' => [
            'through' => OrderItemCouponPurchasable::class,
            'from'    => OrderItemCouponPurchasable::PURCHASABLE,
            'to'      => OrderItemCouponPurchasable::ORDER_ITEM_COUPON,
        ],
    ];

    /**
     * @inheritDoc
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.OrderItemCoupons',
            GridField::create('OrderItemCoupons', null, $this->owner->OrderItemCoupons(),
                GridFieldConfig_RelationEditor::create()));
    }
}
