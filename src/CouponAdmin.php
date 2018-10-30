<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\Admin\ModelAdmin;

/**
 * Class CouponAdmin
 * @package SwipeStripe\Coupons
 */
class CouponAdmin extends ModelAdmin
{
    /**
     * @var string
     */
    private static $menu_title = 'Coupons';

    /**
     * @var string
     */
    private static $url_segment = 'swipestripe/coupons';

    /**
     * @var array
     */
    private static $required_permission_codes = [
        CouponPermissions::EDIT_COUPONS,
    ];

    /**
     * @var array
     */
    private static $managed_models = [
        OrderCoupon::class,
    ];
}
