<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain\Model
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

namespace Jkphl\Antibot\Domain\Model;

/**
 * Validation Result
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain\Model
 */
class ValidationResult
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
     * Return whether the request was valid in general
     *
     * @return bool Valid
     */
    public function isValid(): bool
    {
        return $this->valid;
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
}
