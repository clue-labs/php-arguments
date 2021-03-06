<?php

namespace Clue\Arguments;

/**
 * Splits the given command line string into an array of command arguments
 *
 * @param string $command command line string
 * @return string[] array of command line argument strings
 * @throws \RuntimeException
 */
function split($command)
{
    // map of escaped characters and their replacement
    static $escapes = array(
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
    );

    // whitespace characters count as argument separators
    static $ws = array(
        ' ',
        "\r",
        "\n",
        "\t",
        "\v",
    );

    $i = 0;
    $args = array();

    while (true) {
        // skip all whitespace characters
        for(;isset($command[$i]) && in_array($command[$i], $ws); ++$i);

        // command string ended
        if (!isset($command[$i])) {
            break;
        }

        $inQuote = null;
        $argument = '';

        // read a single argument
        for (; isset($command[$i]); ++$i) {
            $c = $command[$i];

            if ($inQuote === '"') {
                // we're within a "double quoted" string
                if ($c === '\\' && isset($command[$i + 1])) {
                    // any escaped character will be processed
                    $c = $command[++$i];
                    if (isset($escapes[$c])) {
                        // apply mapped character if applicable
                        $argument .= $escapes[$c];
                    } else {
                        // pass through original character otherwise
                        $argument .= $c;
                    }
                    continue;
                } else if ($c === '"') {
                    // double quote ends
                    $inQuote = null;
                    continue;
                }
            } elseif ($inQuote === "'") {
                // we're within a 'single quoted' string
                if ($c === '\\' && isset($command[$i + 1]) && ($command[$i + 1] === "'" || $command[$i + 1] === '\\')) {
                    // escaped single quote or backslash ends up as char in argument
                    $argument .= $command[++$i];
                    continue;
                } elseif ($c === "'") {
                    // single quote ends
                    $inQuote = null;
                    continue;
                }
            } else {
                // we're not within any quotes
                if ($c === '"' || $c === "'") {
                    // start of quotes found
                    $inQuote = $c;
                    continue;
                }elseif (in_array($c, $ws)) {
                    // whitespace character terminates unquoted argument
                    break;
                }
            }

            $argument .= $c;
        }

        // end of argument reached. Still in quotes is a parse error.
        if ($inQuote !== null) {
            throw new \RuntimeException('Still in quotes (' . $inQuote  . ')');
        }

        $args []= $argument;
    }

    return $args;
}
