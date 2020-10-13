<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain\Model
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

namespace Jkphl\Antibot\Ports;

use Jkphl\Antibot\Domain\Contract\ValidationResultInterface;
use Jkphl\Antibot\Domain\Exceptions\ErrorException;

/**
 * Validation Result
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain\Model
 */
class ValidationResult implements ValidationResultInterface
{
    /**
     * Request was valid
     *
     * @var bool
     */
    protected $valid = true;
    /**
     * Whitelisted
     *
     * @var bool
     */
    protected $whitelisted = false;
    /**
     * Named whitelists
     *
     * @var string[]
     */
    protected $whitelists = [];
    /**
     * Blacklisted
     *
     * @var bool
     */
    protected $blacklisted = false;
    /**
     * Named blacklists
     *
     * @var string[]
     */
    protected $blacklists = [];
    /**
     * Error messages
     *
     * @var ErrorException[]
     */
    protected $errors = [];
    /**
     * Skipping validators
     *
     * @var string[]
     */
    protected $skips = [];
    /**
     * Skipped
     *
     * @var bool
     */
    protected $skipped = false;

    /**
     * Return whether the request was valid
     *
     * @return bool Valid
     */
    public function isValid(): bool
    {
        return $this->valid && !$this->skipped;
    }

    /**
     * Return whether the request was invalid
     *
     * @return bool Valid
     */
    public function isFailed(): bool
    {
        return !$this->valid && !$this->skipped;
    }

    /**
     * Set whether the request was valid in general
     *
     * @param bool $valid Valid
     */
    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    /**
     * Return whether the request was whitelisted
     *
     * @return bool Whitelisted
     */
    public function isWhitelisted(): bool
    {
        return $this->whitelisted;
    }

    /**
     * Add a named whitelist
     *
     * @param string $whitelist Whitelist
     */
    public function addWhitelist(string $whitelist): void
    {
        $this->whitelists[] = $whitelist;
        $this->whitelisted  = true;
    }

    /**
     * Return all whitelists
     *
     * @return string[] Whitelist names
     */
    public function getWhitelists(): array
    {
        return $this->whitelists;
    }

    /**
     * Return whether the request was blacklisted
     *
     * @return bool Blacklisted
     */
    public function isBlacklisted(): bool
    {
        return $this->blacklisted;
    }

    /**
     * Add a named blacklist
     *
     * @param string $blacklist Blacklist
     */
    public function addBlacklist(string $blacklist): void
    {
        $this->blacklists[] = $blacklist;
        $this->blacklisted  = true;
    }

    /**
     * Return all blacklists
     *
     * @return string[] Blacklist names
     */
    public function getBlacklists(): array
    {
        return $this->blacklists;
    }

    /**
     * Add an error
     *
     * @param ErrorException $error
     */
    public function addError(ErrorException $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Return all errors
     *
     * @return ErrorException[] Errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Return whether this result has errors
     *
     * @return bool Has errors
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Add a skipping validator
     *
     * @param string $skip Skipping validator
     */
    public function addSkip(string $skip): void
    {
        $this->skips[] = $skip;
        $this->skipped = true;
    }

    /**
     * Return whether this result has skipping validators
     *
     * @return bool Has skipping validators
     */
    public function hasSkips(): bool
    {
        return count($this->skips) > 0;
    }

    /**
     * Return whether a validator skipped this validation
     *
     * @return bool Validation skipped
     */
    public function isSkipped(): bool
    {
        return $this->skipped;
    }
}
