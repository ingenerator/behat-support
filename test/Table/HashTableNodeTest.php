<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\Ingenerator\BehatSupport\Table;


use Behat\Gherkin\Node\TableNode;
use Ingenerator\BehatSupport\Table\HashTableNode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class HashTableNodeTest extends TestCase
{

    public function test_it_parses_array_to_table_node()
    {
        $hash_table_node = HashTableNode::withRows([[1, 2, 3], ["A", "B", "C"]]);
        $this->assertInstanceOf(HashTableNode::class, $hash_table_node);
        $this->assertInstanceOf(TableNode::class, $hash_table_node);
    }

    public function test_it_throws_if_rows_dont_match()
    {
        $this->expectException(\InvalidArgumentException::class);
        HashTableNode::withRows([[1, 2, 3, 4], ["A", "B"]]);
    }

}
