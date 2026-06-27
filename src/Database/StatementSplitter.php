<?php

declare(strict_types=1);

namespace Phalcon\Talon\Database;

use function count;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function strlen;
use function substr;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

final class StatementSplitter
{
    /**
     * @return list<string>
     */
    public static function split(string $sql): array
    {
        $lines = preg_split('#\r\n|\n|\r#', $sql, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $statements      = [];
        $query           = '';
        $delimiter       = ';';
        $delimiterLength = 1;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '-' || $line[0] === '#') {
                continue;
            }

            if (preg_match('#DELIMITER ([\;\$\|\\\]+)#i', $line, $match)) {
                $delimiter       = $match[1];
                $delimiterLength = strlen($delimiter);
                continue;
            }

            $query .= "\n" . $line;

            // Inside an unbalanced pgsql dollar-quoted block: keep accumulating.
            if (
                preg_match_all('/\$[A-Za-z0-9_]*\$/', $query, $matches)
                && count($matches[0]) % 2 !== 0
            ) {
                continue;
            }

            if (substr($query, -$delimiterLength) === $delimiter) {
                $statements[] = trim(substr($query, 0, -$delimiterLength));
                $query        = '';
            }
        }

        if (trim($query) !== '') {
            $statements[] = trim($query);
        }

        return $statements;
    }
}
