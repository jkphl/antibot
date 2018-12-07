<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Validators
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

namespace Jkphl\Antibot\Infrastructure\Validators;

use Jkphl\Antibot\Domain\Antibot;
use Jkphl\Antibot\Infrastructure\Model\AbstractLookupValidator;
use Jkphl\Antibot\Ports\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract IP Lookup Validator
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Validators
 */
abstract class AbstractIpLookupValidator extends AbstractLookupValidator
{
    /**
     * IP address
     *
     * @var string
     */
    protected $ip;

    /**
     * Validate a request
     *
     * @param ServerRequestInterface $request
     * @param Antibot $antibot Antibot instance
     *
     * @return bool
     * @throws InvalidArgumentException If the IP address is unknown
     */
    public function validate(ServerRequestInterface $request, Antibot $antibot): bool
    {
        $serverParams = $request->getServerParams();

        // If the IP address is unknown
        if (empty($serverParams['REMOTE_ADDR'])) {
            throw new InvalidArgumentException(
                InvalidArgumentException::IP_ADDRESS_UNKNOWN_STR,
                InvalidArgumentException::IP_ADDRESS_UNKNOWN
            );
        }

        $this->ip = $serverParams['REMOTE_ADDR'];

        return true;
    }
}
