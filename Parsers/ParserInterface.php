<?php

namespace Yoozi\Miner\Parsers;

interface ParserInterface
{
    /**
     * Perform the parsing process, return the parsed semantic metadata.
     *
     * @return array
     */
    public function parse();
}
