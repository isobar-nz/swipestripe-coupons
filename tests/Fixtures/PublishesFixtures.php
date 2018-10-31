<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons\Tests\Fixtures;

use SilverStripe\Dev\FixtureBlueprint;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\State\FixtureTestState;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Trait PublishesFixtures
 * @package SwipeStripe\Coupons\Tests\Fixtures
 * @mixin SapphireTest
 */
trait PublishesFixtures
{
    /**
     * @param string $class
     * @param null|string $blueprintName
     */
    protected function registerPublishingBlueprint(string $class, ?string $blueprintName = null): void
    {
        $blueprintName = $blueprintName ?? $class;

        /** @var FixtureTestState $state */
        $state = static::$state->getStateByName('fixtures');
        $factory = $state->getFixtureFactory(static::class);

        $blueprint = $factory->getBlueprint($blueprintName);
        if ($blueprint === false) {
            $blueprint = new FixtureBlueprint($blueprintName, $class);
            $factory->define($blueprintName, $blueprint);
        }

        $blueprint->addCallback('afterCreate', function (DataObject $obj) {
            if ($obj->hasExtension(Versioned::class)) {
                /** @var DataObject|Versioned $obj */
                $obj->publishRecursive();
            }
        });
    }
}
