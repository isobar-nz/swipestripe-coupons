<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Order;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Coupons\CouponBehaviour;
use SwipeStripe\Order\Order;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderCoupon
 * @package SwipeStripe\Coupons\Order
 * @property string $Code
 * @property DBPrice $MinSubTotal
 * @property DBPrice $Amount
 * @property float $Percentage
 * @property DBPrice $MaxValue
 * @property string $ValidFrom
 * @property string $ValidUntil
 * @mixin Versioned
 */
class OrderCoupon extends DataObject
{
    use CouponBehaviour;

    /**
     * @var string
     */
    private static $table_name = 'SwipeStripe_Coupons_OrderCoupon';

    /**
     * @var array
     */
    private static $db = [
        'Title'       => 'Varchar',
        'Code'        => 'Varchar',
        'MinSubTotal' => 'Price',
        'Amount'      => 'Price',
        'Percentage'  => 'Percentage(6)',
        'MaxValue'    => 'Price',
        'ValidFrom'   => 'Datetime',
        'ValidUntil'  => 'Datetime',
    ];

    /**
     * @param Order $order
     * @param string $fieldName
     * @return ValidationResult
     */
    public function isValidFor(Order $order, string $fieldName = 'Coupon'): ValidationResult
    {
        $result = ValidationResult::create();

        $orderSubTotal = $order->SubTotal()->getMoney();
        if (!$orderSubTotal->greaterThanOrEqual($this->MinSubTotal->getMoney())) {
            $result->addFieldError($fieldName, _t(self::class . '.SUBTOTAL_TOO_LOW',
                'Sorry, the coupon "{title}" is only valid for orders of at least {min_total}.', [
                    'title'     => $this->Title,
                    'min_total' => $this->MinSubTotal->Nice(),
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

            $minSubTotal = $fields->dataFieldByName('MinSubTotal')
                ->setTitle('Minimum Sub-Total')
                ->setDescription('Minimum order sub-total (total of items and any item add-ons/coupons) ' .
                    'for this coupon to be applied.');

            $fields->dataFieldByName('MaxValue')
                ->setTitle('Maximum Coupon Value')
                ->setDescription('The maximum value of this coupon - only valid for percent off coupons. ' .
                    'E.g. 20% off, maximum discount of $50.');

            $validFrom = $fields->dataFieldByName('ValidFrom');
            $validUntil = $fields->dataFieldByName('ValidUntil');
            $fields->removeByName([
                'MinSubTotal',
                'ValidFrom',
                'ValidUntil',
            ]);
            $fields->insertAfter('MaxValue', ToggleCompositeField::create('Restrictions', 'Restrictions', [
                $minSubTotal,
                $validFrom,
                $validUntil,
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

        $duplicateCode = static::get()->filter([
            'ID:not' => $this->ID,
            'Code'   => $this->Code,
        ]);

        if (empty($this->Code)) {
            $result->addFieldError('Code', _t(self::class . '.CODE_EMPTY',
                'Code cannot be empty.'));
        } elseif ($duplicateCode->exists()) {
            $result->addFieldError('Code', _t(self::class . '.CODE_DUPLICATE',
                'A coupon with that code already exists. Code must be unique.'));
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

        if ($this->MaxValue->getMoney()->isNegative()) {
            $result->addFieldError('MaxValue', _t(self::class . '.MAX_VALUE_NEGATIVE',
                'Max value should not be negative.'));
        }

        if (floatval($this->Percentage) < 0) {
            $result->addFieldError('Percentage', _t(self::class . '.PERCENTAGE_NEGATIVE',
                'Percentage should not be negative.'));
        }

        if ($this->MinSubTotal->getMoney()->isNegative()) {
            $result->addFieldError('MinSubTotal', _t(self::class . '.MIN_SUBTOTAL_NEGATIVE',
                'Minimum sub-total should not be negative.'));
        }

        return $result;
    }

    /**
     * @param Order $order
     * @return DBPrice
     */
    public function AmountFor(Order $order): DBPrice
    {
        $orderSubTotal = $order->SubTotal()->getMoney();

        if ($this->Amount->hasAmount()) {
            $couponAmount = $this->Amount->getMoney();
        } else {
            $couponAmount = $orderSubTotal->multiply(floatval($this->Percentage));
            $maxValue = $this->MaxValue->getMoney();

            if (!$maxValue->isZero() && $couponAmount->greaterThan($maxValue)) {
                $couponAmount = $maxValue;
            }
        }

        // If coupon is more than the order amount, coupon is worth sub-total.
        // E.g. $20 coupon on $10 order makes it free, not -$10
        if ($couponAmount->greaterThan($orderSubTotal)) {
            $couponAmount = $orderSubTotal;
        }

        $this->extend('updateAmountFor', $order, $couponAmount);

        // Coupon amount should always be negative, so it lowers order total
        return DBPrice::create_field(DBPrice::INJECTOR_SPEC, $couponAmount->absolute()->negative());
    }
}
