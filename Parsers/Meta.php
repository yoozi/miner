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
use Yoozi\Miner\Parsers\AbstractParser;
use Buzz\Message\Response;

/**
 * Summarize a public webpage by parsing its meta tags.
 *
 *  Grab some basic metadata about a URL.
 *  In most cases it favors Open Graph (OG) markup,
 *  and will fall back to standard meta tags if necessary.
 *
 * @author Saturn HU <yangg.hu@yoozi.cn>
 */
class Meta extends AbstractParser
{
    /**
     * Open Graph meta tags.
     *
     * @see http://ogp.me/
     * @var array $og
     */
    protected $og = array(
        'title'       => '',
        'keywords'    => array(),
        'author'      => '',
        'description' => '',
        'image'       => ''
    );

    /**
     * {@inheritdoc}
     */
    public function parse()
    {
        if ( ! $this->dom) {
            return $this->meta;
        }

        foreach ($this->dom->getElementsByTagName('meta') as $tag) {

            if ($tag->hasAttribute('property')) {
                $property = strtolower(trim($tag->getAttribute('property')));
                if (strpos($property, 'og:') === 0 && $key = substr($property, 3)) {
                    $this->og[$key] = $tag->getAttribute('content');
                    continue;
                }
            }

            if ($tag->hasAttribute('name')) {
                $name = strtolower(trim($tag->getAttribute('name')));
                if (in_array($name, array('author', 'keywords', 'description'))) {
                    $this->meta[$name] = $tag->getAttribute('content');
                    continue;
                }
            }

            // We'll try to fetch the cover image from microdata (@see schema.org),
            // if it is absent from the open graph tags.
            if (
                ! $this->og['image'] &&
                $tag->hasAttribute('itemprop') &&
                $tag->getAttribute('property') == 'image'
            ) {
                $this->og['image'] = $tag->getAttribute('content');
            }
        }

        return parent::parse();
    }

    /**
     * Return the title of the document.
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->og['title'] ?: parent::getTitle();
    }

    /**
     * Return the description of the document.
     *
     * @return string
     */
    protected function getDescription()
    {
        return $this->coalesce('description');
    }

    /**
     * Return the author of the document.
     *
     * @return string
     */
    protected function getAuthor()
    {
        return $this->coalesce('author');
    }

    /**
     * Return the cover image of the document.
     *
     * @return string
     */
    protected function getImage()
    {
        return $this->coalesce('image');
    }

    /**
     * Return the keywords of the document.
     *
     * @return array
     */
    protected function getKeywords()
    {
        $keywords = $this->coalesce('keywords');

        return $keywords ? array_map('trim', explode(',', $keywords)) : array();
    }

    /**
     * Helper function to coalesce the meta tag values.
     *
     * @param  string $tag
     * @param  mixed  $default
     * @return mixed
     */
    private function coalesce($tag, $default = null)
    {
        return $this->og[$tag] ?: $this->meta[$tag] ?: $default;
    }
}
