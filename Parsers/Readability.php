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

use Yoozi\Miner\Parsers\AbstractParser;

/**
 * PHP Port of Arc90's Readability.
 *
 *  Summarize a webpage using Arc90's open source implementation.
 *
 * @author mingcheng <i.feelinglucky#gmail.com>
 * @link   http://www.gracecode.com/
 * @author Tuxion <team#tuxion.nl>
 * @link   http://tuxion.nl/
 * @author Saturn HU <yangg.hu@yoozi.cn>
 * @link   http://golem.yoozi.cn/
 */
class Readability extends AbstractParser
{
    /**
     * Attribute to store the content score.
     *
     * @var string
     */
    const ATTR_CONTENT_SCORE = "contentScore";

    /**
     * Parsed content.
     *
     * @var string
     */
    protected $content = '';

    /**
     * Dom for the parsed content box.
     *
     * @var \DOMDocument
     */
    protected $contentDom;

    /**
     * List of parent paragraph nodes within a content box.
     *
     * @var array
     */
    private $parentNodes = array();

    /**
     * Junk tags to be deleted.
     *
     * @var array
     */
    private $junkTags = array(
        "style", "form", "iframe", "script", "button", "input", "textarea",
        "noscript", "select", "option", "object", "applet", "basefont",
        "bgsound", "blink", "canvas", "command", "menu", "nav", "datalist",
        "embed", "frame", "frameset", "keygen", "label", "marquee", "link"
    );

    /**
     * Junk attributes to be deleted.
     *
     * @var array
     */
    private $junkAttrs = array(
        "style", "class", "onclick", "onmouseover", "align", "border", "margin"
    );

    /**
     * {@inheritdoc}
     */
    public function parse()
    {
        if (! $this->dom || ! $topBox = $this->getTopBox()) {
            return $this->meta;
        }

        $this->contentDom = new \DOMDocument('1.0');
        $this->contentDom->appendChild($this->contentDom->importNode($topBox, true));

        foreach ($this->junkTags as $tag) {
            $this->removeJunkTag($tag);
        }

        foreach ($this->junkAttrs as $attr) {
            $this->removeJunkAttr($attr);
        }

        $this->content = mb_convert_encoding(
            $this->contentDom->saveHTML(),
            self::DOM_DEFAULT_CHARSET,
            "HTML-ENTITIES"
        );

        return parent::parse();
    }

    /**
     * Return the main content of the document.
     *
     * @return string
     */
    protected function getDescription()
    {
        return $this->content;
    }

    /**
     * Get Leading Image Url
     *
     * @return string|null
     */
    protected function getImage()
    {
        $images = $this->contentDom->getElementsByTagName("img");

        if ($images->length && $image = $images->item(0)) {
            return $image->getAttribute("src");
        }

        return null;
    }

    /**
     * 根据评分获取页面主要内容的盒模型
     *
     * @see    https://github.com/feelinglucky/php-readability/blob/master/lib/Readability.inc.php
     * @return \DOMNode
     */
    private function getTopBox()
    {
        // 获得页面所有的章节
        $allParagraphs = $this->dom->getElementsByTagName("p");

        // Study all the paragraphs and find the chunk that has the best score.
        // A score is determined by things like: Number of <p>'s, commas, special classes, etc.
        $i = 0;
        while ($paragraph = $allParagraphs->item($i++)) {
            $parentNode   = $paragraph->parentNode;
            $contentScore = intval($parentNode->getAttribute(self::ATTR_CONTENT_SCORE));
            $className    = $parentNode->getAttribute("class");
            $id           = $parentNode->getAttribute("id");

            // Look for a special classname
            if (preg_match("/(comment|meta|footer|footnote)/i", $className)) {
                $contentScore -= 50;
            } elseif (preg_match(
                "/((^|\\s)(post|hentry|entry[-]?(content|text|body)?|article[-]?(content|text|body)?)(\\s|$))/i",
                $className
            )) {
                $contentScore += 25;
            }

            // Look for a special ID
            if (preg_match("/(comment|meta|footer|footnote)/i", $id)) {
                $contentScore -= 50;
            } elseif (preg_match(
                "/^(post|hentry|entry[-]?(content|text|body)?|article[-]?(content|text|body)?)$/i",
                $id
            )) {
                $contentScore += 25;
            }

            // Add a point for the paragraph found
            // Add points for any commas within this paragraph
            if (strlen($paragraph->nodeValue) > 10) {
                $contentScore += strlen($paragraph->nodeValue);
            }

            // 保存父元素的判定得分
            $parentNode->setAttribute(self::ATTR_CONTENT_SCORE, $contentScore);

            // 保存章节的父元素，以便下次快速获取
            array_push($this->parentNodes, $parentNode);
        }

        $topBox = null;

        // Assignment from index for performance.
        //     See http://www.peachpit.com/articles/article.aspx?p=31567&seqNum=5
        for ($i = 0, $len = sizeof($this->parentNodes); $i < $len; $i++) {
            $parentNode      = $this->parentNodes[$i];
            $contentScore    = intval($parentNode->getAttribute(self::ATTR_CONTENT_SCORE));
            $orgContentScore = intval($topBox ? $topBox->getAttribute(self::ATTR_CONTENT_SCORE) : 0);

            if ($contentScore && $contentScore > $orgContentScore) {
                $topBox = $parentNode;
            }
        }

        return $topBox;
    }

    /**
     * 删除 DOM 元素中所有的 $attr 元素
     *
     * @param  string $attr
     * @return \DOMDocument
     */
    private function removeJunkAttr($attr)
    {
        $tags = $this->contentDom->getElementsByTagName("*");

        $i = 0;
        while ($tag = $tags->item($i++)) {
            $tag->removeAttribute($attr);
        }

        return $this->contentDom;
    }

    /**
     * 删除 DOM 元素中所有的 $tagName 标签
     *
     * @param  string $tagName
     * @return \DOMDocument
     */
    private function removeJunkTag($tagName)
    {
        $tags = $this->contentDom->getElementsByTagName($tagName);

        while ($tag = $tags->item(0)){
            $parentNode = $tag->parentNode;
            $parentNode->removeChild($tag);
        }

        return $this->contentDom;
    }
}
