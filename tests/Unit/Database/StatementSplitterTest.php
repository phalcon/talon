<?php

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Unit\Database;

use Phalcon\Talon\Database\StatementSplitter;
use PHPUnit\Framework\TestCase;

final class StatementSplitterTest extends TestCase
{
    public function testSplitsOnSemicolonAndSkipsComments(): void
    {
        $sql = "-- a comment\nCREATE TABLE a(id int);\n# another\nINSERT INTO a VALUES (1);\n";

        $this->assertSame(
            ['CREATE TABLE a(id int)', 'INSERT INTO a VALUES (1)'],
            StatementSplitter::split($sql)
        );
    }

    public function testKeepsDollarQuotedBlockTogether(): void
    {
        $sql = "CREATE FUNCTION f() RETURNS int AS \$\$\nBEGIN\nRETURN 1;\nEND;\n\$\$ LANGUAGE plpgsql;\n";

        $result = StatementSplitter::split($sql);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('RETURN 1;', $result[0]);
    }

    public function testTrailingStatementWithoutDelimiterIsReturned(): void
    {
        $this->assertSame(['SELECT 1'], StatementSplitter::split('SELECT 1'));
    }
}
