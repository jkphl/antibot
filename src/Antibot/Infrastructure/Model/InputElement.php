<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Model
 * @author     Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright  Copyright © 2020 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license    http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2020 Joschi Kuphal <joschi@kuphal.net>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

namespace Jkphl\Antibot\Infrastructure\Model;

/**
 * Input Element
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Model
 */
class InputElement
{
    /**
     * Element Attributes
     *
     * @var string[]
     */
    protected $attributes;
    /**
     * Renderer
     *
     * @var \Closure
     */
    protected $renderer;

    /**
     * Constructor
     *
     * @param string[] $attributes    Element Attributes
     * @param \Closure|null $renderer Optional: Renderer
     */
    public function __construct(array $attributes = [], \Closure $renderer = null)
    {
        $this->attributes = $attributes;
        $this->renderer   = $renderer;
    }

    /**
     * Return the Element attributes
     *
     * @return string[] Element attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Serialize the Input Element
     *
     * @return string HTML
     */
    public function __toString(): string
    {
        $html = '<input';
        foreach ($this->attributes as $name => $value) {
            $html .= ' '.htmlspecialchars($name).'="'.htmlspecialchars($value).'"';
        }
        $html .= '/>';

        try {
            if ($this->renderer !== null) {
                return strval(call_user_func($this->renderer, $this, $html));
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $html;
    }

    /**
     * Return all serializable properties
     *
     * The renderer closure must be omitted in order to make the input element serializable
     *
     * @return string[] Serializable properties
     */
    public function __sleep()
    {
        return ['attributes'];
    }
}
