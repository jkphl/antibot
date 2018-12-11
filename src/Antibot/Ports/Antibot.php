<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports
 * @author     Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright  Copyright © 2018 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license    http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2018 Joschi Kuphal <joschi@kuphal.net>
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

namespace Jkphl\Antibot\Ports;

use Jkphl\Antibot\Ports\Contract\ValidatorInterface;
use Jkphl\Antibot\Ports\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Antibot Facade
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports
 */
class Antibot extends \Jkphl\Antibot\Domain\Antibot
{
    /**
     * Set the prefix
     *
     * @param string $prefix Prefix
     */
    public function setPrefix(string $prefix): void
    {
        $prefix = trim($prefix);
        if (!strlen($prefix)) {
            throw new InvalidArgumentException(
                sprintf(InvalidArgumentException::INVALID_PREFIX_STR, $prefix),
                InvalidArgumentException::INVALID_PREFIX
            );
        }
        $this->prefix = $prefix;
    }

    /**
     * Return the Antibot armor
     *
     * @param ServerRequestInterface $request Request
     *
     * @return string Antibot armor (HTML)
     */
    public function armor(ServerRequestInterface $request)
    {
        return implode('', array_map('strval', $this->armorInputs($request)));
    }
}
