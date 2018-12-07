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
use Jkphl\Antibot\Domain\Exceptions\BlacklistValidationException;
use Jkphl\Antibot\Infrastructure\Model\AbstractLookupValidator;
use Jkphl\Antibot\Ports\Contract\LookupStrategyInterface;
use Jkphl\Antibot\Ports\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Parameter Blacklist Validator
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Validators
 */
class ParameterBlacklistValidator extends AbstractLookupValidator
{
    /**
     * Parameter name
     *
     * @var string
     */
    protected $parameter;
    /**
     * Parameter type
     *
     * @var string
     */
    protected $type;
    /**
     * Use $_GET parameter
     *
     * @var string
     */
    const GET = 'GET';
    /**
     * Use $_POST parameter
     *
     * @var string
     */
    const POST = 'POST';

    /**
     * Constructor
     *
     * @param LookupStrategyInterface $strategy Lookup strategy
     * @param string $parameter                 Parameter name
     * @param string $type                      Parameter type
     */
    public function __construct(LookupStrategyInterface $strategy, string $parameter, string $type = self::GET)
    {
        parent::__construct($strategy);
        $this->parameter = $parameter;
        $this->type      = $type;
    }

    /**
     * Validate a request
     *
     * @param ServerRequestInterface $request Request
     * @param Antibot $antibot                Antibot instance
     *
     * @return bool
     * @throws InvalidArgumentException If the parameter type is invalid
     * @throws InvalidArgumentException If the parameter name is invalid
     */
    public function validate(ServerRequestInterface $request, Antibot $antibot): bool
    {
        switch ($this->type) {
            case static::GET:
                $params = $request->getQueryParams();
                break;
            case static::POST:
                $params = $request->getParsedBody();
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf(InvalidArgumentException::INVALID_PARAMETER_TYPE_STR, $this->type),
                    InvalidArgumentException::INVALID_PARAMETER_TYPE
                );
        }

        // If the parameter name is invalid
        if (empty($this->parameter)) {
            throw new InvalidArgumentException(
                InvalidArgumentException::INVALID_PARAMETER_NAME_STR,
                InvalidArgumentException::INVALID_PARAMETER_NAME
            );
        }

        // If the parameter is present
        if (array_key_exists($this->parameter, $params)
            && $this->strategy->lookup($params[$this->parameter])) {
            throw new BlacklistValidationException($this->parameter);
        }

        return true;
    }
}
