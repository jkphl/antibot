<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain
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

namespace Jkphl\Antibot\Domain;

use Jkphl\Antibot\Domain\Contract\ActorInterface;
use Jkphl\Antibot\Domain\Contract\ValidatorInterface;
use Jkphl\Antibot\Domain\Exceptions\BlacklistValidationException;
use Jkphl\Antibot\Domain\Exceptions\WhitelistValidationException;
use Jkphl\Antibot\Domain\Model\ValidationResult;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Antibot core
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain
 */
class Antibot
{
    /**
     * Antibot configuration
     *
     * @var array
     */
    protected $config;
    /**
     * Antibot prefix
     *
     * @var string
     */
    protected $prefix;
    /**
     * Validators
     *
     * @var ValidatorInterface[]
     */
    protected $validators = [];
    /**
     * Actors
     *
     * @var ActorInterface[]
     */
    protected $actors = [];
    /**
     * Default antibot prefix
     *
     * @var string
     */
    const DEFAULT_PREFIX = 'antibot';

    /**
     * Return the prefix
     *
     * @return string Prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Validate a request
     *
     * @param ServerRequestInterface $request Request
     *
     * @return ValidationResult Validation result
     */
    public function validate(ServerRequestInterface $request): ValidationResult
    {
        $result = new ValidationResult();
        usort($this->validators, [$this, 'sortValidators']);

        // Run through all validators (in order)
        /** @var ValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            try {
                if (!$validator->validate($request, $this)) {
                    $result->setValid(false);
                }

                // If the request failed a blacklist test
            } catch (BlacklistValidationException $e) {
                $result->addBlacklist($e->getMessage());
                $result->setValid(false);

                // If the request passed a whitelist test
            } catch (WhitelistValidationException $e) {
                $result->addWhitelist($e->getMessage());
                break;
            }
        }

        return $result;
    }

    /**
     * Compare and sort validators
     *
     * @param ValidatorInterface $validator1 Validator 1
     * @param ValidatorInterface $validator2 Validator 2
     *
     * @return int Sorting
     */
    protected function sortValidators(ValidatorInterface $validator1, ValidatorInterface $validator2): int
    {
        $validatorPos1 = $validator1->getPosition();
        $validatorPos2 = $validator2->getPosition();
        if ($validatorPos1 == $validatorPos2) {
            return 0;
        }

        return ($validatorPos1 > $validatorPos2) ? 1 : -1;
    }
}
