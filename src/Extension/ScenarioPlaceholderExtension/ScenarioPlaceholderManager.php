<?php

namespace Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Ingenerator\BehatSupport\Param\DateParam;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function array_key_exists;

class ScenarioPlaceholderManager implements EventSubscriberInterface
{

    /**
     * @var array<string,callable>
     */
    private array $callbacks = [];

    /**
     * @var array<string,array<string,string>>
     */
    private array $lookups = [];

    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::BEFORE => ['reset', 10],
        ];
    }

    public function __construct()
    {
        $this->reset();
    }

    public function registerCallback(string $type, callable $callback): void
    {
        if (isset($this->lookups[$type])) {
            throw new ConflictingPlaceholderDefinitionException(
                'Cannot register a callback '.$type.' - it is already defined as a lookup'
            );
        }

        $this->callbacks[$type] = $callback;
    }

    public function registerLookup(string $type, string $key, string $value): void
    {
        if (isset($this->callbacks[$type])) {
            throw new ConflictingPlaceholderDefinitionException(
                'Cannot register a lookup table '.$type.' - it is already defined as a callback'
            );
        }

        $this->lookups[$type][$key] = $value;
    }

    public function reset(): void
    {
        $this->lookups   = [];
        $this->callbacks = [
            'date' => $this->transformDate(...),
        ];
    }

    public function transform(string $type, string $arg): string
    {
        if (isset($this->callbacks[$type])) {
            return $this->callbacks[$type]($arg);
        }

        if (array_key_exists($type, $this->lookups)) {
            if ( ! array_key_exists($arg, $this->lookups[$type])) {
                throw new UndefinedScenarioPlaceholderException(
                    sprintf(
                        '"%s" is not registered in the "%s" placeholder lookup table',
                        $arg,
                        $type,
                    )
                );
            }

            return $this->lookups[$type][$arg];
        }

        throw new UndefinedScenarioPlaceholderException(
            sprintf(
                '"%s" is not a defined scenario placeholder type',
                $type
            )
        );
    }

    private function transformDate(string $arg): string
    {
        return DateParam::parse($arg)->format('Y-m-d');
    }
}
