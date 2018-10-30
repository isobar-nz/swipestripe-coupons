<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

/**
 * Class CouponPermissions
 * @package SwipeStripe\Coupons
 */
final class CouponPermissions implements PermissionProvider
{
    const EDIT_COUPONS = self::class . '.EDIT_COUPONS';

    /**
     * @inheritDoc
     */
    public function providePermissions(): array
    {
        $category = _t(Permission::class . '.SWIPESTRIPE_COUPONS_CATEGORY', 'SwipeStripe Coupons');

        return [
            self::EDIT_COUPONS => [
                'name'     => _t(self::EDIT_COUPONS, 'Edit coupons'),
                'category' => $category,
                'help'     => _t(self::EDIT_COUPONS . '_HELP', 'Edit coupons in the CMS.'),
            ],
        ];
    }
}
