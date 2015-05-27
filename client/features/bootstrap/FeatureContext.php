<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Silex\Application;
use Behat\MinkExtension\Context\MinkContext;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    /**
     * @var Application
     */
    private static $app;

    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }

    /**
     * Deletes the database between each scenario, which causes the tables
     * to be re-created and populated with basic fixtures
     *
     * @BeforeScenario
     */
    public function reloadDatabase()
    {
        self::$app['fixtures_manager']->clearTables();
        self::$app['fixtures_manager']->populateSqliteDb();
    }

    /**
     * @BeforeSuite
     */
    public static function bootstrapApp()
    {
        self::$app = require __DIR__.'/../../bootstrap.php';
    }

    /**
     * @Given /^I click "([^"]*)"$/
     */
    public function iClick($linkName)
    {
        return new Given(sprintf('I follow "%s"', $linkName));
    }

    /**
     * @Given /^there is a user "([^"]*)" with password "([^"]*)"$/
     */
    public function thereIsAUserWithPassword($email, $plainPassword)
    {
        $this->createUser($email, $plainPassword);
    }

    /**
     * @Given /^I am logged in$/
     */
    public function iAmLoggedIn()
    {
        $this->createUser('ryan@knplabs.com', 'foo');

        return array(
            new Given('I am on "/login"'),
            new Given('I fill in "Email" with "ryan@knplabs.com"'),
            new Given('I fill in "Password" with "foo"'),
            new Given('I press "Login!"'),
        );
    }

    /**
     * Call this when you've just redirected to COOP and need to login
     *
     * @Given /^I log into COOP$/
     */
    public function iLogIntoCoop()
    {
        return array(
            // a fixtures user on the server
            new Given('I fill in "Email" with "test@knpuniversity.com"'),
            new Given('I fill in "Password" with "test"'),
            new Given('I press "Login!"'),
        );
    }

    /**
     * Takes you through the whole COOP authorization process.
     *
     * This assumes you are NOT logged in to TopCluck
     *
     * @Given /^I am authorized with Coop$/
     */
    public function iAmAuthorizedWithCoop()
    {
        return array(
            new Given('I am on "/"'),
            new Given('I click "Login"'),
            new Given('I click "Login with COOP"'),
            new Given('I log into COOP'),
            new Given('I click "Yes, I Authorize This Request"'),
        );
    }

    private function createUser($email, $plainPassword)
    {
        /** @var \OAuth2Demo\Client\Storage\Connection $storage */
        $storage = self::$app['connection'];

        return $storage->createUser($email, $plainPassword, 'John'.rand(1, 999), 'Doe'.rand(1, 999));
    }
}
