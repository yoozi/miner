<?php

namespace Yoozi\Miner\Parsers;

use Yoozi\Miner\Config;

interface ParserInterface
{
    /**
     * Perform the parsing process, return the parsed semantic metadata.
     *
     * @return array
     */
    public function parse();

    /**
     * Determine the charset of this document.
     *
     * @return string
     */
    public function charset();
}
