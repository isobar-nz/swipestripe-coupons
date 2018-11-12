<?php
declare(strict_types=1);

namespace SwipeStripe\Coupons;

use SilverStripe\ORM\DataObject;

/**
 * Trait SelfStackThroughObject
 * @package SwipeStripe\Coupons
 * @mixin DataObject
 */
trait SelfStackThroughObject
{
    /**
     * @return null|static
     */
    public function getInverse(): ?self
    {
        return static::get_one(static::class, [
            static::LEFT . 'ID'  => $this->{static::RIGHT . 'ID'},
            static::RIGHT . 'ID' => $this->{static::LEFT . 'ID'},
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->getInverse() === null) {
            $inverse = static::create();
            $inverse->{static::LEFT . 'ID'} = $this->{static::RIGHT . 'ID'};
            $inverse->{static::RIGHT . 'ID'} = $this->{static::LEFT . 'ID'};
            $inverse->write();
        }
    }

    /**
     * @inheritdoc
     */
    protected function onAfterDelete()
    {
        parent::onAfterDelete();

        $inverse = $this->getInverse();
        if ($inverse !== null) {
            $inverse->delete();
        }
    }
}
