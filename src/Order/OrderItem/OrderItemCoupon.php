<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldViewButton;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponBehaviour;
use SwipeStripe\Coupons\Order\OrderCoupon;
use SwipeStripe\Order\Order;
use SwipeStripe\Order\OrderItem\OrderItem;
use SwipeStripe\Order\PurchasableInterface;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderItemCoupon
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property string $Code
 * @property DBPrice $Amount
 * @property float $Percentage
 * @property string $ValidFrom
 * @property string $ValidUntil
 * @property int $MinQuantity
 * @method HasManyList|OrderItemCouponPurchasable[] Purchasables()
 * @mixin Versioned
 */
class OrderItemCoupon extends DataObject
{
    use CouponBehaviour;

    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderItemCoupon';

    /**
     * @var array
     */
    private static $db = [
        'Title'       => 'Varchar',
        'Code'        => 'Varchar',
        'Amount'      => 'Price',
        'Percentage'  => 'Percentage(6)',
        'ValidFrom'   => 'Datetime',
        'ValidUntil'  => 'Datetime',
        'MinQuantity' => 'Int',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Purchasables' => OrderItemCouponPurchasable::class,
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Title'        => 'Title',
        'Code'         => 'Code',
        'DisplayValue' => 'Value',
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Title',
        'Code',
    ];

    /**
     * @param Order $order
     * @param string $fieldName
     * @return ValidationResult
     */
    public function isValidFor(Order $order, string $fieldName = 'Coupon'): ValidationResult
    {
        $result = ValidationResult::create();

        $anyItemsMeetRequirements = false;
        foreach ($this->getApplicableOrderItems($order) as $item) {
            if ($this->isActiveForItem($item)) {
                $anyItemsMeetRequirements = true;
                break;
            }
        }

        if (!$anyItemsMeetRequirements) {
            $result->addFieldError($fieldName, _t(self::class . '.NO_MATCHED_ITEMS', 'Sorry, the coupon ' .
                '"{title}" is not valid for any items in your cart.', [
                'title' => $this->Title,
            ]));
        }

        /** @var DBDatetime $validFrom */
        $validFrom = $this->obj('ValidFrom');
        /** @var DBDatetime $validUntil */
        $validUntil = $this->obj('ValidUntil');
        $now = DBDatetime::now()->getTimestamp();

        if ($this->ValidFrom !== null && $validFrom->getTimestamp() > $now) {
            $result->addFieldError($fieldName, _t(self::class . '.TOO_EARLY',
                'Sorry, the coupon "{title}" is not valid before {valid_from}.', [
                    'title'      => $this->Title,
                    'valid_from' => $validFrom->Nice(),
                ]));
        }

        if ($this->ValidUntil !== null && $validUntil->getTimestamp() < $now) {
            $result->addFieldError($fieldName, _t(self::class . '.TOO_LATE',
                'Sorry, the coupon "{title}" expired at {valid_until}.', [
                    'title'       => $this->Title,
                    'valid_until' => $validUntil->Nice(),
                ]));
        }

        $this->extend('isValidFor', $order, $fieldName, $result);
        return $result;
    }

    /**
     * @param OrderItem $item
     * @return bool
     */
    public function isActiveForItem(OrderItem $item): bool
    {
        $active = $item->getQuantity() >= $this->MinQuantity;

        $this->extend('isActiveForItem', $item, $active);
        return $active;
    }

    /**
     * @param Order $order
     * @return SS_List|OrderItem[]
     */
    public function getApplicableOrderItems(Order $order): SS_List
    {
        return $order->OrderItems()->alterDataQuery(function (DataQuery $query) {
            $or = $query->disjunctiveGroup();

            foreach ($this->Purchasables() as $purchasable) {
                $or->conjunctiveGroup()->where([
                    'PurchasableClass' => $purchasable->PurchasableClass,
                    'PurchasableID'    => $purchasable->PurchasableID,
                ]);
            }
        });
    }

    /**
     * @param PurchasableInterface $purchasable
     * @return bool
     */
    public function isApplicableFor(PurchasableInterface $purchasable): bool
    {
        return $this->Purchasables()->filter([
            'PurchasableClass' => $purchasable->ClassName,
            'PurchasableID'    => $purchasable->ID,
        ])->exists();
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $purchasables = $fields->dataFieldByName('Purchasables');
            if ($purchasables instanceof GridField) {
                $purchasables->setConfig(GridFieldConfig_RecordViewer::create()
                    ->removeComponentsByType(GridFieldViewButton::class));
            }

            $fields->dataFieldByName('Amount')
                ->setDescription('Please only enter one of amount or percentage.');

            $fields->dataFieldByName('Percentage')
                ->setDescription('Enter a decimal value between 0 and 1 - e.g. 0.25 for 25% off. Please ' .
                    'only enter one of amount or percentage.');

            $validFrom = $fields->dataFieldByName('ValidFrom');
            $validUntil = $fields->dataFieldByName('ValidUntil');
            $minQuantity = $fields->dataFieldByName('MinQuantity');
            $minQuantity->setDescription('Minimum quantity of applicable item(s) for this coupon to  ' .
                'apply. Quantity is tested against a single item - e.g. 2 shirts and 2 pants will not satisfy a ' .
                'minimum quantity of 3.');

            $fields->removeByName([
                'ValidFrom',
                'ValidUntil',
                'MinQuantity',
            ]);
            $fields->insertAfter('Percentage', ToggleCompositeField::create('Restrictions', 'Restrictions', [
                $validFrom,
                $validUntil,
                $minQuantity,
            ]));
        });

        return parent::getCMSFields();
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        $result = parent::validate();

        $duplicateCode = static::getByCode($this->Code) ?? OrderCoupon::getByCode($this->Code);
        if ($duplicateCode instanceof self && $duplicateCode->ID === $this->ID) {
            // Don't mark self as duplicate
            $duplicateCode = null;
        }

        if (empty($this->Code)) {
            $result->addFieldError('Code', _t(self::class . '.CODE_EMPTY', 'Code cannot be empty.'));
        } elseif ($duplicateCode !== null) {
            $result->addFieldError('Code', _t(self::class . '.CODE_DUPLICATE',
                'Another coupon with that code ("{other_coupon_title}") already exists. Code must be unique across ' .
                'order item and order coupons.', [
                    'other_coupon_title' => $duplicateCode->Title,
                ]));
        }

        if (!$this->Amount->hasAmount() && !floatval($this->Percentage)) {
            $result->addFieldError('Amount', _t(self::class . '.AMOUNT_PERCENTAGE_EMPTY',
                'One of amount or percentage must be set.'));
        } elseif ($this->Amount->hasAmount() && floatval($this->Percentage)) {
            $result->addFieldError('Percentage', _t(self::class . '.AMOUNT_PERCENTAGE_BOTH_SET',
                'Please set only one of amount and percentage. The other should be zero.'));
        }

        if ($this->Amount->getMoney()->isNegative()) {
            $result->addFieldError('Amount', _t(self::class . '.AMOUNT_NEGATIVE',
                'Amount should not be negative.'));
        }

        if (floatval($this->Percentage) < 0) {
            $result->addFieldError('Percentage', _t(self::class . '.PERCENTAGE_NEGATIVE',
                'Percentage should not be negative.'));
        }

        if (intval($this->MinQuantity) < 0) {
            $result->addFieldError('MinQuantity', _t(self::class . '.MINQUANTITY_NEGATIVE',
                'Minimum quantity should not be negative.'));
        }

        return $result;
    }

    /**
     * @param OrderItem $orderItem
     * @return DBPrice
     */
    public function AmountFor(OrderItem $orderItem): DBPrice
    {
        $orderItemSubTotal = $orderItem->SubTotal->getMoney();

        if ($this->Amount->hasAmount()) {
            $couponAmount = $this->Amount->getMoney();
        } else {
            $couponAmount = $orderItemSubTotal->multiply(floatval($this->Percentage));
        }

        // If coupon is more than the item amount, coupon is worth sub-total.
        // E.g. $20 coupon on $10 of items makes it free, not -$10
        if ($couponAmount->greaterThan($orderItemSubTotal)) {
            $couponAmount = $orderItemSubTotal;
        }

        $this->extend('updateAmountFor', $order, $couponAmount);

        // Coupon amount should always be negative, so it lowers order total
        return DBPrice::create_field(DBPrice::INJECTOR_SPEC, $couponAmount->absolute()->negative());
    }
}
