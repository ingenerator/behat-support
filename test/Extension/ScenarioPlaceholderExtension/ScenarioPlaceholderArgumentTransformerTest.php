<?php

namespace test\Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Definition\Definition;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Environment\Environment;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderArgumentTransformer;
use Ingenerator\BehatSupport\Extension\ScenarioPlaceholderExtension\ScenarioPlaceholderManager;
use PHPUnit\Framework\TestCase;

class ScenarioPlaceholderArgumentTransformerTest extends TestCase
{

    private ScenarioPlaceholderManager $manager;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(ScenarioPlaceholderArgumentTransformer::class, $this->newSubject());
    }

    public function provider_expected_type_support()
    {
        return [
            'string'     => ['foo', TRUE],
            'table'      => [TableNode::fromList(['anything']), TRUE],
            'pystring'   => [new PyStringNode(['a string'], 152), TRUE],
            'unexpected' => [new \DateTimeImmutable, FALSE],
        ];
    }

    /**
     * @dataProvider provider_expected_type_support
     */
    public function test_it_supports_expected_argument_types($argumentValue, $expect)
    {
        $subject = $this->newSubject();
        $this->assertSame(
            $expect,
            $subject->supportsDefinitionAndArgument(
                $this->stubDefinitionCall(),
                0,
                $argumentValue
            )
        );
    }

    /**
     * @testWith ["{{reverse:Bill}}", "lliB"]
     *           ["John and {{reverse:Bill}}", "John and lliB"]
     *           ["John, {{reverse:Bill}}, and Peter", "John, lliB, and Peter"]
     */
    public function test_it_can_transform_simple_strings_using_the_manager($argumentValue, $expect)
    {
        $subject = $this->newSubject();
        $this->assertSame($expect, $subject->transformArgument($this->stubDefinitionCall(), 0, $argumentValue));
    }

    public function provider_parse_tables()
    {
        return [
            'one cell'             => [
                new TableNode([['one', '{{reverse:Bill}}']]),
                new TableNode([['one', 'lliB']]),
            ],
            'multiple cells'       => [
                new TableNode(
                    [
                        ['one', 'two', 'three'],
                        ['any', '{{reverse:Bill}}', '{{date:Y-m-(d+1)}}'],
                        ['any', '{{reverse:Peter}}', '{{date:Y-m-(d-1)}}'],
                    ]
                ),
                new TableNode(
                    [
                        ['one', 'two', 'three'],
                        ['any', 'lliB', (new \DateTimeImmutable('tomorrow'))->format('Y-m-d')],
                        ['any', 'reteP', (new \DateTimeImmutable('yesterday'))->format('Y-m-d')],
                    ]
                ),
            ],
            'partial cell content' => [
                new TableNode([['one'], ['at {{reverse:Bill}} home']]),
                new TableNode([['one'], ['at lliB home']]),
            ],
        ];
    }

    /**
     * @dataProvider  provider_parse_tables
     */
    public function test_it_can_transform_tables_using_the_manager(TableNode $argument, TableNode $expect)
    {
        $subject     = $this->newSubject();
        $transformed = $subject->transformArgument($this->stubDefinitionCall(), 0, $argument);
        $this->assertEquals($expect, $transformed);
        $this->assertNotSame($transformed, $argument, 'Returns a new instance with modified values');
    }

    public function provider_parse_pystrings()
    {
        return [
            'one replacement'       => [
                new PyStringNode(['{{reverse:Bill}}'], 123),
                new PyStringNode(['lliB'], 123),
            ],
            'multiple replacements' => [
                new PyStringNode(['I have {{reverse:Bill}} and', 'also {{reverse:John}}'], 293),
                new PyStringNode(['I have lliB and', 'also nhoJ'], 293),
            ],
        ];
    }

    /**
     * @dataProvider provider_parse_pystrings
     */
    public function test_it_can_transform_pystrings_using_the_manager(PyStringNode $argument, PyStringNode $expect)
    {
        $subject     = $this->newSubject();
        $transformed = $subject->transformArgument($this->stubDefinitionCall(), 0, $argument);
        $this->assertEquals($expect, $transformed);
        $this->assertNotSame($transformed, $argument, 'Returns a new instance with modified values');
    }

    public function provider_unchanged()
    {
        return [
            [new PyStringNode(['nothing', 'to change', 'here'], 203)],
            [new TableNode([['value', 'key'], ['original', 'not {changed}']])],
        ];
    }

    /**
     * @dataProvider provider_unchanged
     */
    public function test_it_returns_original_instances_if_table_or_pystring_unchanged(PyStringNode|TableNode $input)
    {
        $this->assertSame($input, $this->newSubject()->transformArgument($this->stubDefinitionCall(), 0, $input));
    }

    public function test_it_throws_if_called_with_unexpected_arg_type()
    {
        $subject        = $this->newSubject();
        $definitionCall = $this->stubDefinitionCall();
        $argumentValue  = new \DateTimeImmutable;
        $this->assertFalse($subject->supportsDefinitionAndArgument($definitionCall, 0, $argumentValue));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateTimeImmutable');
        $subject->transformArgument($definitionCall, 0, $argumentValue);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ScenarioPlaceholderManager;
        $this->manager->registerCallback('reverse', strrev(...));
    }

    private function newSubject(): ScenarioPlaceholderArgumentTransformer
    {
        return new ScenarioPlaceholderArgumentTransformer($this->manager);
    }


    private function stubDefinitionCall(): DefinitionCall
    {
        return new DefinitionCall(
            $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(FeatureNode::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(StepNode::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock(),
            []
        );
    }
}
