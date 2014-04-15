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
use Buzz\Client\ClientInterface as HttpClientInterface;
use Pdp\Parser as DomainParser;
use Pdp\PublicSuffixListManager;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Yoozi\Miner\Exception\RuntimeException;

/**
 * Extract metadata from HTML source content.
 *
 * @author Saturn HU <yangg.hu@yoozi.cn>
 */
class Extractor implements JsonableInterface, ArrayableInterface
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
    protected $metadata = array();

    /**
     * Holds the to-be parsed raw document.
     *
     * @var string
     */
    protected $document;

    /**
     * Holds the charset of the HTML document.
     *
     * @var string
     */
    protected $charset = null;

    /**
     * Set up the configuration for this extrator.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $this->config = new Config($config);
    }

    /**
     * Run the extraction on the HTML content, and apply $callback if necessary.
     *
     * @param  string                                    $source
     * @param  \Clousure                                 $callback
     * @throws \Yoozi\Miner\Exception\RuntimeException
     * @return \Yoozi\Miner\Extractor
     */
    public function run(\Closure $callback = null)
    {
        if (! $document = $this->document) {
            throw new RuntimeException('Miner can not extract an empty document.');
        }

        $class = 'Yoozi\\Miner\\Parsers\\' . ucfirst($this->config->get('parser'));
        $this->parser = new $class($this->config, $this->toDomDocument($document));

        $metadata = array_merge($this->parser->parse(), $this->metadata);

        return $this->metadata = $callback ? $callback($metadata) : $metadata;
    }

    /**
     * Parse an url address, and fill up the basic metadata.
     *
     * @param  string                       $url
     * @param  \Buzz\Client\ClientInterface $client
     * @return \Yoozi\Miner\Extractor
     */
    public function fromUrl($url, HttpClientInterface $client = null)
    {
        $browser  = new Browser($client);
        $response = $browser->get($url, $this->config->get('headers'));
        $request  = $browser->getLastRequest();

        $this->metadata = array();
        foreach (array('url', 'host', 'domain', 'favicon') as $key) {
            $this->metadata[$key] = $this->{'get' . studly_case($key)}($request);
        }

        if ($response->isSuccessful()) {
            $this->document = $response->getContent();
            $this->charset  = $response->getHeaderAttribute('Content-Type', 'charset');
        }

        return $this;
    }

    /**
     * Set the document directly from HTML source.
     *
     * @param  string                 $source
     * @return \Yoozi\Miner\Extractor
     */
    public function fromSource($source)
    {
        $this->metadata = array();
        $this->document = $source;

        return $this;
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
     * Get charset of the document.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
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
     * Get the url we're currently parsing.
     *
     * @param  Buzz\Message\Request $request
     * @return string
     */
    protected function getUrl($request)
    {
        return $request->getUrl();
    }

    /**
     * Get the host.
     *
     * @param  Buzz\Message\Request $request
     * @return string
     */
    protected function getHost($request)
    {
        return $request->getHost();
    }

    /**
     * Get the fully qualified naked domain name.
     *
     * @param  Buzz\Message\Request $request
     * @return string
     */
    protected function getDomain($request)
    {
        if ($host = filter_var($this->metadata['host'], FILTER_VALIDATE_IP)) {
            return $host;
        }

        $manager = new PublicSuffixListManager;
        $parser = new DomainParser($manager->getList());

        return $parser->parseUrl($this->metadata['url'])->host->registerableDomain;
    }

    /**
     * Returns the favicon image of the webpage.
     *
     * Here we use the free Google favicon web service.
     *
     * @param  \Buzz\Message\Request $request
     * @return string
     */
    protected function getFavicon($request)
    {
        return 'http://www.google.com/s2/favicons?domain=' . $this->metadata['domain'];
    }

    /**
     * Helper function to return the current HTML as a DOMDocument.
     *
     * @param  string       $html
     * @return \DOMDocument
     */
    protected function toDomDocument($html)
    {
        if (! $this->charset) {
            preg_match("/charset=([\w|\-]+);?/", $html, $match);
            $this->charset = isset($match[1]) ? $match[1] : 'utf-8';
        }

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $this->charset);
        $html = $this->sanitize($html);

        $revert = libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'utf-8');

        // DomDocument cannot recognize the HTML5 charset meta tag, which may
        // cause potential problems for processing pages in CJK charset.
        $fix = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $dom->loadHTML($fix . $html);

        libxml_use_internal_errors($revert);

        return $dom;
    }

    /**
     * Sanitize the HTML content to prevent the DOM transformation failure.
     *
     * @param  string $string
     * @return string
     */
    protected function sanitize($string)
    {
        // 剔除多余的 HTML 编码标记，避免解析出错
        preg_match("/charset=([\w|\-]+);?/", $string, $match);
        if (isset($match[1])) {
            $string = preg_replace("/charset=([\w|\-]+);?/", "", $string, 1);
        }

        // Replace all doubled-up <BR> tags with <P> tags, and remove fonts.
        $string = preg_replace("/<br\/?>[ \r\n\s]*<br\/?>/i", "</p><p>", $string);
        $string = preg_replace("/<\/?font[^>]*>/i", "", $string);

        // @see https://github.com/feelinglucky/php-readability/issues/7
        //   - from http://stackoverflow.com/questions/7130867/remove-script-tag-from-html-content
        $string = preg_replace("#<script(.*?)>(.*?)</script>#is", "", $string);

        return trim($string);
    }
}
