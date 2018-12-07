<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Validators
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

namespace Jkphl\Antibot\Ports\Validators;

use Jkphl\Antibot\Domain\Antibot;
use Jkphl\Antibot\Domain\Exceptions\WhitelistValidationException;
use Jkphl\Antibot\Infrastructure\Validators\AbstractIpLookupValidator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * IP based whitelist
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Validators
 */
class IpWhitelistValidator extends AbstractIpLookupValidator
{
    /**
     * Validation order position
     *
     * @var int
     */
    const POSITION = 0;
    /**
     * Whitelist name
     *
     * @var string
     */
    const NAME = 'ip';

    /**
     * Validate a request
     *
     * @param ServerRequestInterface $request
     * @param Antibot $antibot Antibot instance
     *
     * @return bool
     * @throws WhitelistValidationException If the IP address is contained in the whitelist
     */
    public function validate(ServerRequestInterface $request, Antibot $antibot): bool
    {
        parent::validate($request, $antibot);

        // If the IP address is contained in the Whitelist: Skip validations
        if ($this->strategy->lookup($this->ip)) {
            throw new WhitelistValidationException(static::NAME);
        }

        return true;
    }
}
