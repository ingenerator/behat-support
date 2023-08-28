# ApiEmulatorExtension

This extension provides a client, contexts, placeholders and supporting code for interacting with a
running [ingenerator/api_emulator](ingenerator/api_emulator) service to manage state & make assertions.

## Usage

Register the extension in your behat.yml:

```gherkin
# behat.yml
default:
  extensions:
    Ingenerator\BehatSupport\Extension\ApiEmulatorExtension:
      # Optionally, specify the base URL for the emulator service. 
      # This defaults to http://api-emulator-http:9000
      # base_url: http://url-of-my-emulator:8080
      
      # Optionally, specify how long to wait at the start of a suite for the emulator to respond to healthchecks
      # This defaults to 30 seconds
      # healthcheck_wait_timeout_seconds: 15
  
      # Optionally, specify delay between between retries if the emulator is not responding to healthchecks at the start
      # of the suite.
      # This defaults to 250ms
      # healthcheck_retry_interval_ms: 500
```

This will make the client available, and automatically sends a call to reset the emulator's global state before each 
scenario.

### Simple assertions

Include the provided `Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\SimpleApiEmulatorContext`
to register a couple of common / simple assertions:

You can check that:

* The emulator did not receive any requests at all
* Of the requests received, there was only one to a specified method & URL, and it exactly matched an expected payload

For example, this allows something like:

```gherkin
Feature: We can sync changed records

  Scenario: Does nothing when nothing to sync
      # Given there are no records to sync today
      # When  the sync task runs
    Then  the api emulator should not have received any requests

  Scenario: Syncs expected records
      # Given the following records:
      #  | id | sync_due_at |
      #  | 1  | tomorrow    |
      #  | 2  | today       |
      #  | 3  | today       |
      # When  the sync task runs
    Then  the api emulator should have received one POST at http://api-emulator-http:9000/sync with body:
    """
    {
      "record_ids": [2,3]
    }
    """
```

### Placeholder values

You'll notice the example above contains a hardcoded URL to the emulator. This is both verbose and brittle against
configuration changes.

We provide `Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorScenarioPlaceholderContext` which
works with the [Scenario Placeholder extension](./scenario_placeholder_extension.md) to provide a couple of useful
placeholder replacements:

| placeholder                      | description                                                                                               | example                                 |
|----------------------------------|-----------------------------------------------------------------------------------------------------------|-----------------------------------------|
| `{{api_emulator:base_url}}`      | The emulator base URL configured in your behat.yml                                                        | http://api-emulator-http:9000           |
| `{{api_emulator:base_ping_url}}` | An emulator base URL for cases where you don't need a custom handler and any empty 200 response is valid. | http://api-emulator-http:9000/ping-200/ |

Use this like so:

```yaml
# behat.yml
default:
  suites:
    default:
      contexts:
      - Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\SimpleApiEmulatorContext
      - Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorScenarioPlaceholderContext
  extensions:
    Ingenerator\BehatSupport\Extension\ApiEmulatorExtension: ~
```

```gherkin
Feature: System checks if URL is healthy

  Scenario: Ping a URL
    # Given I request a healthcheck of {{api_emulator:base_ping_url}}/user-specified-ping
    Then the api emulator should have received one GET at {{api_emulator:base_ping_url}}/user-specified-ping with body:
    """
    {}
    """
```

### Custom operations

You'll often need to do more complex / custom integrations. Just define a context that implements 
`ApiEmulatorAwareContext` to receive the API client at runtime.

```php
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorAwareContext;
use Ingenerator\BehatSupport\Extension\ApiEmulatorExtension\ApiEmulatorCapturedRequest;use PHPUnit\Framework\Assert;

class MyServiceContext implements Context, ApiEmulatorAwareContext {

    private readonly ApiEmulatorClient $client;

    public function setApiEmulator(ApiEmulatorClient $client): void
    {
        $this->client = $client;
    }
    
    /**
     * @Given /^(?P<email>.+) is registered as customer (?P<customer_number>.+) on the accounting system$/ 
     */
    public function givenUserHasAccount(string $email, string $customer_number): void
    {
        // Set custom state that API emulator handlers can read
        // This will be reset at the start of each scenario
        // See https://github.com/ingenerator/api_emulator#setting-state-from-your-test-code
        $this->client->populateRepository('users/'.md5($email), ['customer_number' => $customer_number]);
    }
    
    /**
     * @Then /^the accounting system should have received updates for the following customers:$/ 
     */
    public function assertCustomerUpdates(TableNode $expected): void
    {
        $all_requests = $this->client->listRequests();
        // ->listRequests returns a collection of all request captured since the start of the scenario.
        // The collection provides a couple of simple assertion methods as well as the ability to inspect
        // the requests and to filter them based on known values or a callable matcher. See the implementation
        // of the collection class for details.
        
        $update_requests = $all_requests->filterByUriAndMethod(
            $this->client->base_url.'/accounting/customers/update',
            'POST'
        );
        
        $actual = array_map(
            fn(ApiEmulatorCapturedRequest $rq) => $rq->parsed_body['customer_number'],
            $update_requests
        );
        
        Assert::assertSame($expected->getColumn(0), $actual);
    }

}
```
