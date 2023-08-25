<?php

namespace Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Transformation\Transformer\ArgumentTransformer;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use function array_map;
use function get_debug_type;
use function is_string;
use function preg_replace_callback;

class ScenarioPlaceholderArgumentTransformer implements ArgumentTransformer
{

    public function __construct(
        private readonly ScenarioPlaceholderManager $placeholder_manager
    ) {
    }

    public function supportsDefinitionAndArgument(DefinitionCall $definitionCall, $argumentIndex, $argumentValue)
    {
        return is_string($argumentValue) || $argumentValue instanceof TableNode ||
               $argumentValue instanceof PyStringNode;
    }

    public function transformArgument(DefinitionCall $definitionCall, $argumentIndex, $argumentValue)
    {
        return match (TRUE) {
            is_string($argumentValue) => $this->transformString($argumentValue),
            $argumentValue instanceof TableNode => $this->transformTable($argumentValue),
            $argumentValue instanceof PyStringNode => $this->transformPyString($argumentValue),
            default => throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported argument "%s" to %s',
                    get_debug_type($argumentValue),
                    __METHOD__
                )
            )
        };
    }

    private function transformString(string $value): string
    {
        return preg_replace_callback(
            '/\{\{(?P<type>.+?):(?P<args>.+?)}}/',
            fn($matches) => $this->placeholder_manager->transform($matches['type'], $matches['args']),
            $value
        );
    }

    private function transformTable(TableNode $value): TableNode
    {
        $transformed_rows = array_map(
            fn($row) => array_map($this->transformString(...), $row),
            $value->getRows()
        );
        if ($transformed_rows !== $value->getRows()) {
            return new TableNode($transformed_rows);
        } else {
            return $value;
        }
    }

    private function transformPyString(PyStringNode $value): PyStringNode
    {
        $original    = $value->getStrings();
        $transformed = array_map($this->transformString(...), $original);

        if ($transformed !== $original) {
            return new PyStringNode($transformed, $value->getLine());
        } else {
            return $value;
        }
    }


}
