<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Exceptions
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

namespace Jkphl\Antibot\Ports\Exceptions;

/**
 * Invalid Argument Exception
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Exceptions
 */
class InvalidArgumentException extends \Jkphl\Antibot\Domain\Exceptions\InvalidArgumentException
{
    /**
     * Invalid prefix
     *
     * @var string
     */
    const INVALID_PREFIX_STR = 'Invalid prefix "%s"';
    /**
     * Invalid prefix
     *
     * @var int
     */
    const INVALID_PREFIX = 1544118177;
    /**
     * Invalid parameter type
     *
     * @var string
     */
    const INVALID_PARAMETER_TYPE_STR = 'Invalid parameter type "%s"';
    /**
     * Invalid parameter type
     *
     * @var int
     */
    const INVALID_PARAMETER_TYPE = 1544178602;
    /**
     * Invalid parameter name
     *
     * @var string
     */
    const INVALID_PARAMETER_NAME_STR = 'Invalid parameter name';
    /**
     * Invalid parameter name
     *
     * @var int
     */
    const INVALID_PARAMETER_NAME = 1544178707;
    /**
     * Unknown IP address
     *
     * @var string
     */
    const IP_ADDRESS_UNKNOWN_STR = 'Unknown IP address (lookup validator)';
    /**
     * Unknown IP address
     *
     * @var int
     */
    const IP_ADDRESS_UNKNOWN = 1544175065;
}
