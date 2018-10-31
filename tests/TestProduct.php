<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\Order\PurchasableInterface;
use SwipeStripe\Price\DBPrice;

/**
 * Class TestProduct
 * @package SwipeStripe\Shipping\Tests
 * @property string $Title
 * @property string $Description
 * @property DBPrice $Price
 */
class TestProduct extends DataObject implements PurchasableInterface
{
    /**
     * @var array
     */
    private static $db = [
        'Title' => DBVarchar::class,
        'Price' => DBPrice::class,
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class => Versioned::class . '.versioned',
    ];

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->Title;
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): DBPrice
    {
        return $this->getField('Price');
    }
}
