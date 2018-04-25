<?php

namespace Ingenerator\BehatSupport\Extension\TestDataExtension;

use test\behat\support\BehatDatabase\BehatDbManager;
use test\support\TestData\TestDataManager;

/**
 * Identifies that a behat context needs to interact with test data and the database
 */
interface BehatDbAwareContext
{
    /**
     * Injects the database and test data manager framework
     *
     * @param BehatDbManager  $db
     * @param TestDataManager $test_data
     *
     * @return void
     */
    public function setBehatDb(BehatDbManager $db, TestDataManager $test_data);

}
