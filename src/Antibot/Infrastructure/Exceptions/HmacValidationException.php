<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Exceptions
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

namespace Jkphl\Antibot\Infrastructure\Exceptions;

use Jkphl\Antibot\Domain\Exceptions\ErrorException;

/**
 * HMAC Validation Exception
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Exceptions
 */
class HmacValidationException extends ErrorException
{
    /**
     * Invalid request method order
     *
     * @var string
     */
    const INVALID_REQUEST_METHOD_ORDER_STR = 'Invalid request method order';
    /**
     * Invalid request method order
     *
     * @var int
     */
    const INVALID_REQUEST_METHOD_ORDER = 1544292604;
    /**
     * Invalid request timing
     *
     * @var string
     */
    const INVALID_REQUEST_TIMING_STR = 'Invalid request timing';
    /**
     * Invalid request timing
     *
     * @var int
     */
    const INVALID_REQUEST_TIMING = 1544292684;
}
