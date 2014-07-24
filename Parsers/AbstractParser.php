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
        'title'        => null,
        'author'       => null,
        'keywords'     => array(),
        'description'  => null,
        'image'        => null
    );

    /**
     * Dom document we're manipulating with.
     *
     * @var \DomDocument $dom
     */
    protected $dom;

    /**
     * Holds the configuration of extrator.
     *
     * @var \Yoozi\Miner\Config $config
     */
    protected $config;

    /**
     * Create the config and DOM.
     *
     * @param  \Yoozi\Miner\Config $config
     * @param  \DomDocument        $dom
     * @return void
     */
    public function __construct(Config $config, \DomDocument $dom)
    {
        $this->config = $config;
        $this->dom    = $dom;
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
     * Return the title of the document, or null on failure.
     *
     * @return string|null
     */
    protected function getTitle()
    {
        if ($node = $this->firstDomNode("title")) {
            // @see http://stackoverflow.com/questions/717328/how-to-explode-string-right-to-left
            $title  = trim($node->nodeValue);
            $result = array_map('strrev', explode('-', strrev($title)));

            return trim(sizeof($result) > 1 ? array_pop($result) : $title);
        }

        if ($node = $this->firstDomNode("h1")) {
            return $node->textContent;
        }

        return null;
    }

    /**
     * Return the first node in the DOM tree, or null on failure.
     *
     * @param  string        $tagName
     * @return \DOMNode|null
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
