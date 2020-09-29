<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Extension\TestDataExtension;


use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Doctrine\ORM\EntityManager;
use Ingenerator\KohanaExtras\DependencyContainer\DependencyContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use test\behat\support\BehatDatabase\BehatDbManager;
use test\support\TestData\TestDataManager;

/**
 * Responsible for managing a pair of behat database / test data manager instances for a scenario
 *
 * Clears all internal handles at the start of each scenario by binding to the behat
 * event framework, so that every scenario has a completely clean set of entity manager
 * / repository / test data managers.
 *
 * @package Ingenerator\BehatSupport\Extension\TestDataExtension
 */
class BehatDbMaintainer implements EventSubscriberInterface
{
    /**
     * @var BehatDbManager
     */
    protected $behat_db;

    /**
     * @var DependencyContainer
     */
    protected $dependencies;

    /**
     * @var TestDataManager
     */
    protected $test_data;

    public function __construct(DependencyContainer $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public static function getSubscribedEvents()
    {
        return [
            ScenarioTested::BEFORE => ['resetTestDb', 10],
        ];
    }

    /**
     * @return BehatDbManager
     */
    public function getBehatDb()
    {
        if ( ! $this->behat_db) {
            $this->initDatabase();
        }

        return $this->behat_db;

    }

    protected function initDatabase()
    {
        $em              = $this->getEntityManager();
        $this->behat_db  = new BehatDbManager($this->dependencies);
        $this->test_data = new TestDataManager($em);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->dependencies->get('doctrine.entity_manager');
    }

    /**
     * @return TestDataManager
     */
    public function getTestDataManager()
    {
        if ( ! $this->test_data) {
            $this->initDatabase();
        }

        return $this->test_data;
    }

    /**
     * Clears out all state ready for a new scenario.
     * Also clears the entity manager to free up memory and ensure clean state there too.
     */
    public function resetTestDb()
    {
        $this->behat_db = $this->test_data = NULL;
        $this->getEntityManager()->clear();
    }


}
