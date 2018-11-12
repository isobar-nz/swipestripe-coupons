<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Price\PriceField;
use UncleCheese\DisplayLogic\Extensions\DisplayLogic;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Trait CouponBehaviour
 * @package SwipeStripe\Coupons
 * @mixin DataObject
 * @mixin Versioned
 * @property string $DisplayValue
 */
trait CouponBehaviour
{
    use CouponCMSPermissions;

    /**
     * @var array
     */
    private static $indexes = [
        'code-unique' => [
            'type' => 'unique',
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
     * @see CouponInterface::getByCode()
     * @param string $code
     * @return null|static
     */
    public static function getByCode(string $code): ?CouponInterface
    {
        return static::get_one(static::class, [
            'Code' => $code,
        ]);
    }


    /**
     * @inheritDoc
     */
    public function populateDefaults()
    {
        parent::populateDefaults();

        try {
            // Set default random code - 4 bytes = 8 chars
            $this->Code = bin2hex(random_bytes(4));
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayValue(): string
    {
        return $this->Amount->hasAmount()
            ? $this->Amount->getValue()
            : $this->Percentage * 100 . '%';
    }

    /**
     * @param FormField $field
     * @return Wrapper|DisplayLogic
     */
    protected function wrapDisplayLogic(FormField $field): Wrapper
    {
        $fieldList = $field->getContainerFieldList();
        $wrapper = Wrapper::create($field);

        if ($fieldList) {
            $fieldList->replaceField($field->getName(), $wrapper);
        }

        return $wrapper;
    }

    /**
     * @param PriceField $amount
     * @param NumericField|DisplayLogic $percentage
     * @param PriceField $maxValue
     */
    protected function setUpAmountPercentageHideBehaviour(
        PriceField $amount,
        NumericField $percentage,
        PriceField $maxValue
    ): void {
        $amountAmountField = $amount->getAmountField();
        $epsilon = 0.000000001; // No PHP_FLOAT_EPSILON in PHP7.1 - to emulate >=0 by doing < epsilon

        // Show amount field if coupon has no value or amount value set
        $this->wrapDisplayLogic($amount)->displayIf($percentage->getName())->isLessThan($epsilon)->isEmpty()
            ->orIf($amountAmountField->getName())->isGreaterThan(0);

        // Show percentage field if coupon has no value or percentage value set
        $percentage->displayIf($amountAmountField->getName())->isLessThan($epsilon)->isEmpty()
            ->orIf($percentage->getName())->isGreaterThan(0);

        // Show max value field if percentage value set
        $this->wrapDisplayLogic($maxValue)->displayIf($percentage->getName())->isGreaterThan(0);
    }
}
