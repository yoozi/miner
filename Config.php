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

use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

/**
 * Miner Configuration.
 *
 * @author Saturn HU <yangg.hu@yoozi.cn>
 */
class Config implements ArrayableInterface, JsonableInterface
{
    /**
     * Holds the configrations for parsers.
     *
     * @var array
     */
    protected $items = array(
        'parser'     => 'hybrid',
        'headers'    => array(
            'User-Agent' => 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)'
        ),
        'strip_tags' => false
    );

    /**
     * Setup the configration.
     *
     * @param  array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->items = array_merge($this->items, $options);
    }

    /**
     * Get a config item using "dot" notation.
     *
     * <code>
     * // Get the HTML parser implementation class.
     * $parser = $this->get('parser');
     * </code>
     *
     * @param  array $options
     * @return mixed
     */
    public function get($key)
    {
        return array_get($this->items, $key, null);
    }

    /**
     * Set a config item using "dot" notation.
     *
     * <code>
     * // Set the File parser implementation class.
     * $this->set('parser', 'Foo');
     * </code>
     *
     * @param  array $options
     * @return void
     */
    public function set($key, $val)
    {
        $this->items = array_set($this->items, $key, $val);
    }

    /**
     * The option array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * The config options in json.
     *
     * @param  int    $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->items, $options);
    }
}
