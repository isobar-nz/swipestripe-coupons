<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order\OrderItem;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponBehaviour;
use SwipeStripe\Coupons\Order\OrderCoupon;
use SwipeStripe\Order\OrderItem\OrderItem;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderItemCoupon
 * @package SwipeStripe\Coupons\Order\OrderItem
 * @property string $Code
 * @property DBPrice $Amount
 * @property float $Percentage
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
        'Title'      => 'Varchar',
        'Code'       => 'Varchar',
        'Amount'     => 'Price',
        'Percentage' => 'Percentage(6)',
    ];

    /**
     * @param OrderItem $orderItem
     * @param string $fieldName
     * @return ValidationResult
     */
    public function isValidFor(OrderItem $orderItem, string $fieldName = 'Coupon'): ValidationResult
    {
        $result = ValidationResult::create();

        $this->extend('isValidFor', $orderItem, $fieldName, $result);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->dataFieldByName('Amount')
                ->setDescription('Please only enter one of amount or percentage.');

            $fields->dataFieldByName('Percentage')
                ->setDescription('Enter a decimal value between 0 and 1 - e.g. 0.25 for 25% off. Please ' .
                    'only enter one of amount or percentage.');
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
