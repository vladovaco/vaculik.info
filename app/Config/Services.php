<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use Modules\Core\Services\DeadlineAggregator;
use Modules\Core\Services\HouseholdContext;

/**
 * Services Configuration File.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function householdContext(bool $getShared = true): HouseholdContext
    {
        if ($getShared) {
            return static::getSharedInstance('householdContext');
        }

        return new HouseholdContext();
    }

    public static function deadlines(bool $getShared = true): DeadlineAggregator
    {
        if ($getShared) {
            return static::getSharedInstance('deadlines');
        }

        return new DeadlineAggregator();
    }
}
