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

use Jkphl\Antibot\Infrastructure\Exceptions\RuntimeException;
use Jkphl\Antibot\Ports\Contract\LookupStrategyInterface;

/**
 * Abstract Lookup Validator
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Model
 */
abstract class AbstractLookupValidator extends AbstractValidator implements \Serializable
{
    /**
     * Lookup strategy
     *
     * @var LookupStrategyInterface
     */
    protected $strategy;

    /**
     * Constructor
     *
     * @param LookupStrategyInterface $strategy Lookup strategy
     */
    public function __construct(LookupStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Serialize this lookup validator
     *
     * @return string
     */
    public function serialize(): string
    {
        $data             = get_object_vars($this);
        $data['strategy'] = get_class($this->strategy);

        return serialize($data);
    }

    /**
     * Deserialization
     *
     * @param string $serialized Deserialized lookup validator
     *
     * @throws RuntimeException If the lookup validator should be deserialized
     */
    public function unserialize($serialized)
    {
        throw new RuntimeException(
            RuntimeException::UNSUPPORTED_LOOKUP_VALIDATOR_DESERIALIZATION_STR,
            RuntimeException::UNSUPPORTED_LOOKUP_VALIDATOR_DESERIALIZATION
        );
    }
}
