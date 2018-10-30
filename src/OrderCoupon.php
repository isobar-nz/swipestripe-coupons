<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Order\Order;
use SwipeStripe\Price\DBPrice;

/**
 * Class OrderCoupon
 * @package SwipeStripe\Coupons
 * @property string $Code
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
        'Title'      => 'Varchar',
        'Code'       => 'Varchar',
        'Amount'     => 'Price',
        'Percentage' => 'Percentage(6)',
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
     * @return bool
     */
    public function isValidFor(Order $order): bool
    {
        $active = true;

        $this->extend('isActive', $order, $active);
        return $active;
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

        if ($this->Amount->hasAmount() && $this->Percentage > 0) {
            $result->addFieldError('Percentage', _t(self::class . '.AMOUNT_PERCENTAGE_BOTH_SET',
                'Please set only one of amount and percentage. The other should be zero.'));
        }

        if ($this->Amount->getMoney()->isNegative()) {
            $result->addFieldError('Amount', _t(self::class . '.AMOUNT_NEGATIVE',
                'Amount should not be negative.'));
        }

        if ($this->Percentage < 0) {
            $result->addFieldError('Percentage', _t(self::class . '.PERCENTAGE_NEGATIVE',
                'Percentage should not be negative.'));
        }

        return $result;
    }

    /**
     * @param Order $order
     * @return DBPrice
     */
    public function AmountFor(Order $order): DBPrice
    {
        // Must be negative, to reduce the order amount
        $couponAmount = $this->Amount->hasAmount()
            ? $this->Amount->getMoney()->multiply(-1)
            : $order->SubTotal()->getMoney()->multiply(-1 * (1 - $this->Percentage));

        return DBPrice::create_field(DBPrice::INJECTOR_SPEC, $couponAmount);
    }
}
