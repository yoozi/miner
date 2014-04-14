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

/**
 * Hybrid parser.
 *
 *  We take Readability as the primary parser, and Meta as its fallback.
 *
 * @author Saturn HU <yangg.hu@yoozi.cn>
 */
class Hybrid extends AbstractParser
{
    /**
     * {@inheritdoc}
     */
    public function parse()
    {
        if (! $this->dom) {
            return $this->meta;
        }

        foreach (array('meta', 'readability') as $vendor) {
            $class = 'Yoozi\\Miner\\Parsers\\' . ucfirst($vendor);
            $$vendor = new $class($this->config, $this->dom);
            $$vendor = $$vendor->parse();
        }

        $suggest = $meta;
        $suggest['image'] = $meta['image'] ?: $readability['image'];
        $suggest['description'] = $readability['description'] ?: $meta['description'];

        return $suggest;
    }
}
