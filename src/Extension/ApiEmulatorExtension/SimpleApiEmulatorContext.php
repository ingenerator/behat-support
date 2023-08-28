<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use function json_decode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

class SimpleApiEmulatorContext implements Context, ApiEmulatorAwareContext
{
    private readonly ApiEmulatorClient $client;

    public function setApiEmulator(ApiEmulatorClient $client): void
    {
        $this->client = $client;
    }

    /**
     * @Then /^the api emulator should not have received any requests$/
     */
    public function assertNoRequests(): void
    {
        $this->client->listRequests()->assertEmpty();
    }

    /**
     * @Then /^the api emulator should have received one (?P<method>.+) at (?P<url>.+) with body:$/
     */
    public function assertCapturedOneRequestWithBody(string $method, string $url, PyStringNode $expected_body): void
    {
        $request = $this->client->listRequests()->assertSingleRequestTo($method, $url);

        $expected = json_decode($expected_body->getRaw(), associative: true, flags: JSON_THROW_ON_ERROR);

        try {
            Assert::assertSame($expected, $request->parsed_body,);
        } catch (ExpectationFailedException $e) {
            throw new ApiEmulatorAssertionFailedException(
                sprintf(
                    "Payload of %s to %s did not match expectation:\n%s",
                    $method,
                    $url,
                    trim($e->getComparisonFailure()?->getDiff() ?? $e->getMessage())
                )
            );
        }
    }
}
