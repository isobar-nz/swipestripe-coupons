<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SwipeStripe\ShopPermissions;

/**
 * Trait CouponCMSPermissions
 * @package SwipeStripe\Coupons
 * @mixin DataObject
 */
trait CouponCMSPermissions
{
    /**
     * @see DataObject::canView()
     * @param null|Member $member
     * @return bool
     */
    public function canView($member = null)
    {
        return Permission::checkMember($member, ShopPermissions::VIEW_ORDERS) || Permission::checkMember($member,
                CouponPermissions::EDIT_COUPONS) || parent::canView($member);
    }

    /**
     * @see DataObject::canEdit()
     * @param null|Member $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Permission::checkMember($member, CouponPermissions::EDIT_COUPONS) || parent::canEdit($member);
    }

    /**
     * @see DataObject::canDelete()
     * @param null|Member $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        return Permission::checkMember($member, CouponPermissions::EDIT_COUPONS) || parent::canDelete($member);
    }

    /**
     * @see DataObject::canCreate()
     * @param null|Member $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::checkMember($member, CouponPermissions::EDIT_COUPONS) || parent::canCreate($member,
                $context);
    }

    /**
     * @see Versioned::canPublish()
     * @param null|Member $member
     * @return bool
     */
    public function canPublish($member = null): bool
    {
        return Permission::checkMember($member, CouponPermissions::EDIT_COUPONS) || parent::canPublish($member);
    }
}
