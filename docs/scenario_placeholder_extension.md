# ScenarioPlaceholderExtension

This extension provides a mechanism for using placeholders in your feature files that will be replaced
at runtime. For example, to refer to a date relative to now, an ID for a test customer, or similar.

## Usage

There are no configuration options, just  register the  extension  in your behat.yml.
```yaml
# behat.yml
default:
  extensions:
    Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension: ~
```

Then in a feature  file, reference placeholders  as `{{<type>:<arg>}}` where `type` represents a 
registered type of placeholder and  arg is an arbitary value to be replaced.

Placeholders can be used anywhere within:
*  a simple step argument (e.g. defined as an argument within the step text)
* a TableNode
* a PyStringNode

For example, with the built-in `date` placeholder type you could do this:
```gherkin
Feature: Do something on the 2nd of next month

  Scenario: Do something
    When I fill in "Date" with "{{date:Y-(m+1)-02}}"
    And  I fill in "Time" with "13:50:20"
    And  I save the form
    Then I should see the following list of tasks:
      | scheduled_at                 | what          |
      | {{date:Y-(m+1)-02}} 13:50:20 | run-something |

```

Note that placeholders are replaced  anywhere inside the argument and can be preceded or  followed by
hardcoded values.

### Built-in placeholder types

Out of the box, we support:

| type | description                                                                                                           |
|------|-----------------------------------------------------------------------------------------------------------------------|
| date | Passes the argument through `Ingenerator\BehatSupport\Param\DateParam::parse()` and returns a date formatted as Y-m-d |

### Registering custom placeholders

Placeholders can be registered either as a simple key => value lookup table, or as a callback function. Note that these 
are mutually exclusive -  attempting to register a callback for a placeholder type that has already been registered as a 
lookup (or vice versa) will throw an exception.

The simplest way  to register placeholders is in your contexts, by implementing the `ScenarioPlaceholderAwareContext`:

```php
use Behat\Behat\Context\Context;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderAwareContext;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\UndefinedScenarioPlaceholderException;

class SomeContext implements Context, ScenarioPlaceholderAwareContext {

    public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void
    {
        // Register a callback that transforms `{{reverse:James}}` to `semaJ`
        $manager->registerCallback('reverse', fn($arg) => strrev($arg));
    }
}

class UserContext implements Context, ScenarioPlaceholderAwareContext {

    private readonly ScenarioPlaceholderManager $placeholders;
     
    public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void
    {
        $this->placeholders = $manager;
    }
    
    /** 
     * @Given /^a user with email :email$/ 
     */
    public function givenAUser(string $email):void 
    {
        $user = $this->createUser($email); // or whatever
        $this->placeholders->registerLookup('customer_id', $email, $user->getCustomerId());
        
        // Now your scenarios can look like this:
        // Given a user with email brian@foo.test
        // And   a user with email james@foo.test
        // When  I visit "/customers/{{customer_id:brian@foo.test}}"
        // And   I merge the record into "james@foo.test"
        // Then  I should be on "/customers/{{customer_id:james@foo.test}}"
        // And   I should see that brian@foo.test was merged into james@foo.test         
    }
}


class OrdersContext implements Context, ScenarioPlaceholderAwareContext {

    public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void
    {
        // Register a callback that can provide details about the most recent order
        $manager->registerCallback(
            'most_recent_order', 
            fn($arg) => match($arg) {
                'id' => $this->loadMostRecentOrder()->getId(),
                'tracking_url' => $this->loadMostRecentOrder()->getTrackingUrl(),
                default => throw new UndefinedScenarioPlaceholderException('Unsupported most_recent_order arg: '.$arg)
            });
    }
    
    private function loadMostRecentOrder(): Order {
        // or whatever
        return $this->orders_repo->findMostRecent();
    }
}
```

### Referencing placeholders within context classes

You will usually be able to  use  placeholders in your feature files and ignore them completely in 
your contexts. However, it may  sometimes be useful to access placeholders from code -  in 
particular if you need to chain different types of placeholder together.

For example with this scenario:

```gherkin
Given I login as "james@foo.test"
And   I place an order
Then  james@foo.test should receive an email with the subject "Order {{customer_last_order:james@foo.test}} is on the way!"
```

Assuming you already have a `customer_id` lookup table as in the example above, you could implement the 
`customer_last_order` placeholder type as:

```php
class OrdersContext implements Context, ScenarioPlaceholderAwareContext {

    public function setScenarioPlaceholders(ScenarioPlaceholderManager $manager): void
    {
        $manager->registerCallback(
            'customer_last_order',
            function ($arg) use ($manager) {
                $customer_id = $manager->transform('customer_id', $arg);
                return $this->loadLastOrderForCustomer($customer_id)->getId();
            }
        );
    }
    
    private function loadLastOrderForCustomer(int $customer_id): Order {
        // or whatever
        $orders = $this->orders_repo->findForCustomer($customer_id);
        return array_pop($orders);
    }
}
```


## Why not behat's own step argument transformations?
We decided not to use the step argument transformations built into behat for a couple of reasons:

* The `@Transform` methods declared in feature contexts are not that easy to find or debug, and
  it's not obvious when looking at a feature file when a placeholder / transformation is in use.

* Behat's transformations operate separately on the different argument types (parameters in the 
  step text vs PyStrings vs Tables). We find we often need the same placeholder in different
  ways - perhaps within a table in a setup step, then a data entry field, then a string 
  representation of a JSON object.

Therefore we wanted a more explicit declarative style that worked identically across all types 
of step arguments. Our extension hooks into behat's own transformation flow, but has a custom
implementation of searching for and replacing values within the arguments.
