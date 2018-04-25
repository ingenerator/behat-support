<?php

namespace Ingenerator\BehatSupport\Extension\TestDataExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;

/**
 * Assigns the database state to contexts that require it
 */
class BehatDbInitialiser implements ContextInitializer
{

    /**
     * @var BehatDbMaintainer
     */
    protected $db_maintainer;

    public function __construct(BehatDbMaintainer $db_maintainer)
    {
        $this->db_maintainer = $db_maintainer;
    }

    /**
     * Called once for each context at the start of each scenario, injects the dependencies if required
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof BehatDbAwareContext) {
            $context->setBehatDb($this->db_maintainer->getBehatDb(), $this->db_maintainer->getTestDataManager());
        }
    }

}
