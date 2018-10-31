<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Order\Order;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderCoupon
 * @package SwipeStripe\Coupons
 * @property string $Code
 * @property DBPrice $MinSubTotal
 * @property DBPrice $Amount
 * @property float $Percentage
 * @mixin Versioned
 */
class OrderCoupon extends DataObject
{
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
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'code-unique' => [
            'type'    => 'unique',
            'columns' => [
                'Code',
            ],
        ],
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class => Versioned::class,
    ];

    /**
     * @param string $code
     * @return null|OrderCoupon
     */
    public static function getByCode(string $code): ?self
    {
        return static::get_one(static::class, [
            'Code' => $code,
        ]);
    }

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
                'Sorry, this coupon is only valid for orders of at least {min_total}.', [
                    'min_total' => $this->MinSubTotal->Nice(),
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

            $fields->dataFieldByName('MinSubTotal')
                ->setTitle('Minimum Sub-Total')
                ->setDescription('Minimum order sub-total (total of items and any item add-ons/coupons) ' .
                    'for this coupon to be applied.');
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

        // Must be negative, to reduce the order amount
        $couponAmount = $this->Amount->hasAmount()
            ? $this->Amount->getMoney()->multiply(-1)
            : $order->SubTotal()->getMoney()->multiply(-1 * $this->Percentage);

        // If sub-total with coupon is negative, coupon is worth sub-total.
        // E.g. $20 coupon on $10 order makes it free, not -$10
        if ($orderSubTotal->add($couponAmount)->isNegative()) {
            $couponAmount = $orderSubTotal->multiply(-1);
        }

        return DBPrice::create_field(DBPrice::INJECTOR_SPEC, $couponAmount);
    }
}
