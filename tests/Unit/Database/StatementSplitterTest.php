<?php

/**
 * This file is part of the Phalcon Talon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function testHandlesDelimiterChange(): void
    {
        $sql = "DELIMITER ;;\nCREATE A;;\nDELIMITER ;\nSELECT 1;\n";

        $this->assertSame(['CREATE A', 'SELECT 1'], StatementSplitter::split($sql));
    }

    public function testBalancedDollarQuotedStatementEndsAtDelimiter(): void
    {
        $sql = "CREATE FUNCTION f() AS \$\$\nBEGIN RETURN 1; END;\n\$\$;\nSELECT 2;\n";

        $this->assertSame(
            ["CREATE FUNCTION f() AS \$\$\nBEGIN RETURN 1; END;\n\$\$", 'SELECT 2'],
            StatementSplitter::split($sql)
        );
    }

    public function testHyphenAsSecondCharacterIsNotAComment(): void
    {
        $this->assertSame(['D-1'], StatementSplitter::split("D-1;\n"));
    }

    public function testIndentedCommentLinesAreSkipped(): void
    {
        $sql = "  -- indented comment\nSELECT 1;\n";

        $this->assertSame(['SELECT 1'], StatementSplitter::split($sql));
    }

    public function testLowercaseDelimiterDirectiveIsHonored(): void
    {
        $sql = "delimiter ;;\nCREATE A;;\ndelimiter ;\nSELECT 1;\n";

        $this->assertSame(['CREATE A', 'SELECT 1'], StatementSplitter::split($sql));
    }

    public function testMultiLineStatementKeepsLineBreaks(): void
    {
        $this->assertSame(["SELECT\n1"], StatementSplitter::split("SELECT\n1;\n"));
    }
}
