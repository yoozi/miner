<?php

/*
 * This file is part of the Yoozi Golem package.
 *
 * (c) Yoozi Inc. <hello@yoozi.cn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Yoozi\Miner;

use Buzz\Browser;
use Yoozi\Miner\Config;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

/**
 * Extract the metadata from a public url.
 *
 * @author Saturn HU <yangg.hu@yoozi.cn>
 */
class Extractor extends Browser implements JsonableInterface, ArrayableInterface
{
    /**
     * Holds the parser.
     *
     * @var \Yoozi\Miner\Parsers\ParserInterface
     */
    protected $parser;

    /**
     * Holds the configuration of extrator.
     *
     * @var \Yoozi\Miner\Config
     */
    protected $config;

    /**
     * Holds the ultimate parsed metadata.
     *
     * @var array
     */
    protected $metadata;

    /**
     * Parse an url address, return the semantic parts.
     *
     * @param  string $url
     * @param  \Clousure $callback
     * @return \Yoozi\Miner\Extractor
     */
    public function run($url, Closure $callback = null)
    {
        $config   = $this->getConfig();
        $response = $this->get($url, $config->get('headers'));
        $request  = $this->getLastRequest();

        if ($response->isSuccessful()) {

            $this->parser = $this->factory($config, $request, $response);
            $metadata = $this->parser->parse($config);

            return $this->metadata = $callback ? $callback($metadata) : $metadata;
        }

        // $this->exception($response);
    }

    /**
     * Set config.
     *
     * @param  \Yoozi\Miner\Config $config
     * @return void
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get config of this extractor.
     *
     * @return \Yoozi\Miner\Config
     */
    public function getConfig()
    {
        return $this->config ?: new Config;
    }

    /**
     * Get the parser object we're currently using.
     *
     * @return mixed
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Array representation of the parsed result.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->metadata;
    }

    /**
     * JSON representation of the parsed result.
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->metadata, $options);
    }

    /**
     * Resolve and initialize the fully qualified parser object.
     *
     * @param  \Yoozi\Miner\Config $config
     * @param  \Buzz\Message\Request $request
     * @param  \Buzz\Message\Response $response
     * @return mixed
     */
    private function factory($config, $request, $response)
    {
        $class = 'Yoozi\\Miner\\Parsers\\' . ucfirst($config->get('parser'));

        return new $class($config, $request, $response);
    }
}
