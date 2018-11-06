<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

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
     * @return null|static
     */
    public static function getByCode(string $code): ?self
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
}
