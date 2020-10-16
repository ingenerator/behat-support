<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\BehatSupport\Table;


use Behat\Gherkin\Node\TableNode;
use InvalidArgumentException;
use function array_keys;
use function in_array;
use function json_encode;

class HashTableNode extends TableNode
{

    protected function __construct(array $hash_rows)
    {
        if (empty($hash_rows)) {
            throw new InvalidArgumentException('Table is empty');
        }

        $rows = [];
        foreach ($hash_rows as $row) {
            if (empty($rows)) {
                $rows[0] = array_keys($row);
            } elseif ($rows[0] !== array_keys($row)) {
                throw new \InvalidArgumentException("".json_encode(array_keys($row)));
            }
            $rows[] = array_values($row);
        }

        parent::__construct($rows);
    }

    /**
     * @param TableNode $source
     * @param string    $column
     * @param string[]  $want_values
     *
     * @return HashTableNode
     */
    public static function filterRowsFromTable(TableNode $source, string $column, array $want_values): HashTableNode
    {
        $filtered = [];
        foreach ($source->getHash() as $row) {
            if (in_array($row[$column], $want_values)) {
                $filtered[] = $row;
            }
        }

        return static::withRows($filtered);
    }

    /**
     * @param array $rows
     *
     * @return HashTableNode
     */
    public static function withRows(array $rows): HashTableNode
    {
        return static::withRows($rows);
    }

    /**
     * @param array  $rows
     * @param string $sort_column
     *
     * @return HashTableNode
     */
    public static function withSortedRows(array $rows, string $sort_column): HashTableNode
    {
        usort(
            $rows,
            function ($a, $b) use ($sort_column) {
                return strcmp($a[$sort_column], $b[$sort_column]);
            }
        );

        return static::withRows($rows);
    }

}
