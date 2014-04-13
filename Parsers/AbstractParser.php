<?php

/*
 * This file is part of the Yoozi Golem package.
 *
 * (c) Yoozi Inc. <hello@yoozi.cn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Yoozi\Miner\Parsers;

use Yoozi\Miner\Config;
use Yoozi\Miner\Parsers\ParserInterface;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Pdp\Parser as DomainParser;
use Pdp\PublicSuffixListManager;

/**
 * Abstract metadata parser.
 *
 * @author Saturn HU <yangg.hu@yoozi.cn>
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * Data attributes to export.
     *
     * @var array
     */
    public $meta = array(
        'url'          => '',
        'host'         => '',
        'domain'       => '',
        'favicon'      => '',
        'title'        => '',
        'author'       => '',
        'keywords'     => array(),
        'description'  => '',
        'image'        => ''
    );

    /**
     * Buzz request object.
     *
     * @var \Buzz\Message\Request $request
     */
    protected $request;

    /**
     * Buzz response object.
     *
     * @var \Buzz\Message\Response $response
     */
    protected $response;

    /**
     * Dom document we're manipulating with.
     *
     * @var \DomDocument $dom
     */
    protected $dom;

    /**
     * Dom document charset.
     */
    const DOM_DEFAULT_CHARSET = "utf-8";

    /**
     * Create the request and response.
     *
     * @param  \Buzz\Message\Request
     * @param  \Buzz\Message\Response
     * @return void
     */
    public function __construct(Config $config, Request $request, Response $response)
    {
        $this->config   = $config;
        $this->request  = $request;
        $this->response = $response;
        $this->dom = $this->toDomDocument($response, $this->charset());
    }

    /**
     * {@inheritdoc}
     */
    public function parse()
    {
        if (! $this->dom) {
            return $this->meta;
        }

        foreach ($this->meta as $key => $value) {
            $mutator = 'get' . studly_case($key);
            if (method_exists($this, $mutator)) {
                // We will use a mutator to process an attribute value if
                // we found one, otherwise we'll do nothing.
                $this->meta[$key] = $this->{$mutator}();
            }
        }

        if ($this->config->get('strip_tags') && $this->meta['description']) {
            $this->meta['description'] = strip_tags($this->meta['description']);
        }

        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function charset()
    {
        $charset = $this->response->getHeaderAttribute('Content-Type', 'charset');

        if (! $charset) {
            preg_match("/charset=([\w|\-]+);?/", $this->response->getContent(), $match);
            return isset($match[1]) ? $match[1] : 'utf-8';
        }

        return $charset;
    }

    /**
     * Get the url we're currently parsing.
     *
     * @return string
     */
    protected function getUrl()
    {
        return $this->request->getUrl();
    }

    /**
     * Get the host.
     *
     * @return string
     */
    protected function getHost()
    {
        return $this->request->getHost();
    }

    /**
     * Get the fully qualified naked domain name.
     *
     * @return string
     */
    protected function getDomain()
    {
        if ($host = filter_var($this->meta['host'], FILTER_VALIDATE_IP)) {
            return $host;
        }

        $manager = new PublicSuffixListManager;
        $parser = new DomainParser($manager->getList());

        return $parser->parseUrl($this->meta['url'])->host->registerableDomain;
    }

    /**
     * Returns the favicon image of the webpage.
     *
     * Here we use the free Google favicon web service.
     *
     * @return string
     */
    protected function getFavicon()
    {
        return 'http://www.google.com/s2/favicons?domain=' . $this->meta['domain'];
    }

    /**
     * Return the title of the document.
     *
     * @return string|null
     */
    protected function getTitle()
    {
        if ($node = $this->firstDomNode("title")) {
            // @see http://stackoverflow.com/questions/717328/how-to-explode-string-right-to-left
            $title  = trim($node->nodeValue);
            $result = array_map('strrev', explode(' - ', strrev($title)));

            return sizeof($result) > 1 ? array_pop($result) : $title;
        }

        if ($node = $this->firstDomNode("h1")) {
            return $node->textContent;
        }

        return null;
    }

    /**
     * Helper function to return the current message as a DOMDocument.
     *
     * @param  \Buzz\Message\Response $response
     * @param  string $charset
     * @return \DOMDocument
     */
    protected function toDomDocument($response, $charset)
    {
        $html = mb_convert_encoding(
            $response->getContent(),
            'HTML-ENTITIES',
            $charset ?: self::DOM_DEFAULT_CHARSET
        );
        $html = $this->sanitize($html);

        $revert = libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', self::DOM_DEFAULT_CHARSET);

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

    /**
     * Return the first node in the DOM tree, or null on failure.
     *
     * @param  string $tagName
     * @return \DOMNode
     */
    protected function firstDomNode($tagName)
    {
        $nodes = $this->dom->getElementsByTagName($tagName);
        if ($nodes->length && $node = $nodes->item(0)) {
            return $node;
        }

        return null;
    }
}
