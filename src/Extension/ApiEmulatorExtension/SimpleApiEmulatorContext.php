<?php

namespace Ingenerator\BehatSupport\Extension\ApiEmulatorExtension;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Ingenerator\PHPUtils\StringEncoding\JSON;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_PRETTY_PRINT;

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

        $expected = JSON::decode($expected_body->getRaw());

        if ($request->parsed_body === $expected) {
            return;
        }

        $diff = (new Differ(new UnifiedDiffOutputBuilder("\n--- Expected\n+++ Actual\n")))
            ->diff(
                json_encode($expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                json_encode($request->parsed_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );

        throw new ApiEmulatorAssertionFailedException(
            sprintf(
                "Payload of %s to %s did not match expectation:\n%s",
                $method,
                $url,
                trim($diff)
            )
        );
    }
}
