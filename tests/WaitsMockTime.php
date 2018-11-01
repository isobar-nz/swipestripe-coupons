<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests;

use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Trait WaitsMockTime
 * @package SwipeStripe\Coupons\Tests
 */
trait WaitsMockTime
{
    /**
     * @param int $seconds
     */
    protected function mockWait(int $seconds = 5): void
    {
        DBDatetime::set_mock_now(DBDatetime::now()->getTimestamp() + $seconds);
    }
}
