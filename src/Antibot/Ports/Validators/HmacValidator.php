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
use Jkphl\Antibot\Domain\Exceptions\InvalidRequestMethodOrderException;
use Jkphl\Antibot\Domain\Exceptions\SkippedValidationException;
use Jkphl\Antibot\Infrastructure\Exceptions\HmacValidationException;
use Jkphl\Antibot\Infrastructure\Factory\HmacFactory;
use Jkphl\Antibot\Infrastructure\Model\AbstractValidator;
use Jkphl\Antibot\Infrastructure\Model\InputElement;
use Jkphl\Antibot\Ports\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HMAC Validator
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Validators
 */
class HmacValidator extends AbstractValidator
{
    /**
     * Request method vector
     *
     * @var string[]
     */
    protected $methodVector = null;
    /**
     * Request submission times
     *
     * @var float[]
     */
    protected $submissionTimes = null;
    /**
     * Validation order position
     *
     * @var int
     */
    const POSITION = 100;
    /**
     * GET request
     *
     * @var string
     */
    const METHOD_GET = 'GET';
    /**
     * POST request
     *
     * @var string
     */
    const METHOD_POST = 'POST';
    /**
     * Minimum submission time
     *
     * @var float
     */
    const MINIMUM_SUBMISSION = 10;
    /**
     * Minimum submission time for follow-up submissions
     *
     * @var float
     */
    const MINIMUM_FOLLOWUP_SUBMISSION = 3;
    /**
     * Maximum submission time
     *
     * @var float
     */
    const MAXIMUM_SUBMISSION = 3600;
    /**
     * Block access
     *
     * @var string
     */
    const BLOCK = 'BLOCK';

    /**
     * Set the request method vector
     *
     * @param string $previous Previous request
     * @param string $current  Current request
     */
    public function setMethodVector(string $previous = null, string $current = null): void
    {
        // If the request method vector should be unset
        if ($previous === null) {
            $this->methodVector = null;

            return;
        }

        $this->methodVector = [$this->validateRequestMethod($previous), $this->validateRequestMethod($current)];
    }

    /**
     * Sanitize and validate a request method
     *
     * @param string $method Request method
     *
     * @return string Validated request method
     * @throws InvalidArgumentException If the request method is invalid
     */
    protected function validateRequestMethod(string $method): string
    {
        $method = strtoupper($method);
        if ($method !== static::METHOD_GET && $method !== static::METHOD_POST) {
            throw new InvalidArgumentException(
                sprintf(InvalidArgumentException::INVALID_REQUEST_METHOD_STR, $method),
                InvalidArgumentException::INVALID_REQUEST_METHOD
            );
        }

        return $method;
    }

    /**
     * Sanitize and set the submission times
     *
     * @param float $max              Maximum submission time
     * @param float $min              Minimum submission time
     * @param float|null $minFollowUp Minimum submission time for follow-up submissions
     */
    public function setSubmissionTimes(float $max = null, float $min = null, float $minFollowUp = null): void
    {
        // If the submission times should be unset
        if ($max === null) {
            $this->submissionTimes = null;

            return;
        }

        $max                   = min(floatval($max), static::MAXIMUM_SUBMISSION);
        $min                   = max(floatval($min), static::MINIMUM_SUBMISSION);
        $minFollowUp           = ($minFollowUp === null)
            ? $min : max(floatval($minFollowUp), static::MINIMUM_FOLLOWUP_SUBMISSION);
        $this->submissionTimes = [$min, $minFollowUp, $max];
    }

    /**
     * Validate a request
     *
     * @param ServerRequestInterface $request Request
     * @param Antibot $antibot                Antibot instance
     *
     * @return bool
     * @throws HmacValidationException
     * @throws SkippedValidationException If no Antibot data has been submitted
     */
    public function validate(ServerRequestInterface $request, Antibot $antibot): bool
    {
        $data = $antibot->getData();

        // If no Antibot data has been submitted
        if ($data === null) {
            throw new SkippedValidationException(static::class);
        }

        return empty($data['hmac']) ? false : $this->validateHmac($data['hmac'], $request, $antibot);
    }

    /**
     * Create protective form HTML
     *
     * @param ServerRequestInterface $request Request
     * @param Antibot $antibot                Antibot instance
     *
     * @return InputElement[] HMTL input elements
     */
    public function armor(ServerRequestInterface $request, Antibot $antibot): array
    {
        $now   = null;
        $hmac  = $this->calculateHmac($request, $antibot, $now);
        $armor = [
            new InputElement([
                'type'  => 'hidden',
                'name'  => $antibot->getParameterPrefix().'[hmac]',
                'value' => $hmac
            ])
        ];
        // Add the timestamp field
        if ($now !== null) {
            $armor[] = new InputElement([
                'type'  => 'hidden',
                'name'  => $antibot->getParameterPrefix().'[ts]',
                'value' => intval($now)
            ]);
        }

        return $armor;
    }

    /**
     * Decrypt and validate an HMAC
     *
     * @param string $hmac                    HMAC
     * @param ServerRequestInterface $request Request
     * @param Antibot $antibot                Antibot instance
     *
     * @return bool HMAC is valid
     * @throws HmacValidationException If the request method order is invalid
     * @throws HmacValidationException If the request timing is invalid
     */
    protected function validateHmac(string $hmac, ServerRequestInterface $request, Antibot $antibot): bool
    {
        $decrypted      = false;
        $previousMethod = null;
        $hmacParams     = [$antibot->getUnique()];

        // Short-circuit blocked HMAC
        $hmacBlock   = $hmacParams;
        $hmacBlock[] = self::BLOCK;
        if (HmacFactory::createFromString(serialize($hmacBlock), $antibot->getUnique()) === $hmac) {
            return false;
        }

        // If the request method vector should be used
        if (!empty($this->methodVector)) {
            $serverParams  = $request->getServerParams();
            $requestMethod = empty($serverParams['REQUEST_METHOD']) ? 'EMPTY' : $serverParams['REQUEST_METHOD'];
            if ($requestMethod !== $this->methodVector[1]) {
                throw new HmacValidationException(
                    HmacValidationException::INVALID_REQUEST_METHOD_ORDER_STR,
                    HmacValidationException::INVALID_REQUEST_METHOD_ORDER
                );
            }

            $hmacParams[] = $this->methodVector[0];
        }

        // If submission time checks are enabled
        if (!empty($this->submissionTimes)) {
            list($first, $min, $max) = $this->submissionTimes;
            $now       = time();
            $initial   = $now - $first;
            $delay     = null;
            $data      = $antibot->getData();
            $timestamp = empty($data['ts']) ? null : $data['ts'];

            // If a timestamp has been submitted
            if ($timestamp
                && (($timestamp + $min) <= $now)
                && (($timestamp + $max) >= $now)
                && (
                    $this->probeTimedHmac($hmac, $antibot, $hmacParams, $timestamp, $timestamp > $initial)
                    || (($timestamp <= $initial) ?
                        $this->probeTimedHmac($hmac, $antibot, $hmacParams, $timestamp, true) : false
                    )
                )
            ) {
//                $delay     = $now - $timestamp;
                return true;
            } else {
                // Run through the valid seconds range
                for ($time = $now - $min; $time >= $now - $max; --$time) {
                    // Probe the current timestamp
                    if ($this->probeTimedHmac($hmac, $antibot, $hmacParams, $time, $time > $initial)
                        || (($time <= $initial)
                            && $this->probeTimedHMAC($hmac, $antibot, $hmacParams, $time, true)
                        )
                    ) {
//                        $delay     = $now - $time;
                        return true;
                    }
                }
            }

            throw new HmacValidationException(
                HmacValidationException::INVALID_REQUEST_TIMING_STR,
                HmacValidationException::INVALID_REQUEST_TIMING
            );
        }

        $currentHMAC = HmacFactory::createFromString(serialize($hmacParams), $antibot->getUnique());

        return $hmac === $currentHMAC;
    }

    /**
     * Probe a timed HMAC
     *
     * @param string $hmac      HMAC
     * @param Antibot $antibot  Antibot instance
     * @param array $hmacParams HMAC params
     * @param int $timestamp    Timestamp
     * @param bool $followUp    Is a follow-up request
     *
     * @return bool HMAC is valid
     */
    protected function probeTimedHmac(
        string $hmac,
        Antibot $antibot,
        array $hmacParams,
        int $timestamp,
        bool $followUp = false
    ): bool {
        if ($followUp) {
            $hmacParams[] = true;
        }
        $hmacParams[] = $timestamp;
        $currentHMAC  = HmacFactory::createFromString(serialize($hmacParams), $antibot->getUnique());

        echo 'Current HMAC: '.$currentHMAC;

        return $currentHMAC == $hmac;
    }

    public function _decryptHmac($hmac)
    {
        // If submission time checks are enabled
        if ($this->_submissionTimeEnabled()) {
            $minimum  = intval($this->_settings['time']['minimum']);
            $maximium = intval($this->_settings['time']['maximum']);
            $first    = max($minimum, intval($this->_settings['time']['first']));
            $now      = time();
            $initial  = $now - $first;
            // If a timestamp hint has been submitted: Probe this first
            if ($this->_timestamp && (($this->_timestamp + $minimum) <= $now) && (($this->_timestamp + $maximium) >= $now) && $this->_info('Probing timestamp hint first') && (
                    $this->_probeTimedHMAC($hmac, $hmacParams, $this->_timestamp, $this->_timestamp > $initial) ||
                    (($this->_timestamp <= $initial) ? $this->_probeTimedHMAC($hmac, $hmacParams, $this->_timestamp,
                        true) : false))
            ) {
                $this->_delay = $now - $this->_timestamp;
                $decrypted    = true;
                // Else (or if decryption failed for some reason: Probe the valid time range
            } else {
                // Run through the valid seconds range
                for ($time = $now - $minimum; $time >= $now - $maximium; --$time) {
                    // Probe the current timestamp
                    if ($this->_probeTimedHMAC($hmac, $hmacParams, $time,
                            $time > $initial) || (($time <= $initial) && $this->_probeTimedHMAC($hmac, $hmacParams,
                                $time, true))
                    ) {
                        $this->_delay = $now - $time;
                        $decrypted    = true;
                        break;
                    }
                }
            }
            // Else: Check for HMAC match
        } else {
            $currentHMAC = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($hmacParams));
            $decrypted   = $hmac == $currentHMAC;
            $this->_debug('Probing HMAC with parameters', $hmacParams);
            $this->_debug('Current HMAC:', $currentHMAC);
        }
        // Register the initial HTTP method in case decryption was successfull
        if ($decrypted && $previousMethod) {
            $this->_method = $previousMethod;
        }

        return $decrypted;
    }

    /**
     * Probe a set of HMAC parameters with timestamp (for both initial or follow-up requests)
     *
     * @param \string $hmac      HMAC
     * @param \array $hmacParams HMAC parameters
     * @param \int $timestamp    Timestamp
     * @param \boolean $followUp Follow-up request
     *
     * @return \boolean                HMAC matches
     */
    protected function _probeTimedHMAC($hmac, array $hmacParams, $timestamp, $followUp = false)
    {
        if ($followUp) {
            $hmacParams[] = true;
        }
        $hmacParams[] = $timestamp;
        $currentHMAC  = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($hmacParams));
        $this->_debug('Probing HMAC with parameters', $hmacParams);
        $this->_debug('Current HMAC:', $currentHMAC);

        return $currentHMAC == $hmac;
    }

    /**
     * Create and return the submission HMAC
     *
     * @param \int $now Current timestamp
     *
     * @return \string                    Submission HMAC
     */
    protected function _hmac(&$now = null)
    {
        $hmacParams = array($this->_token);
        // If session token checks are enabled
        if ($this->_sessionTokenEnabled()) {
            $hmacParams[] = session_id();
        }
        // If there is an invalid current HMAC
        if ($this->_valid === false) {
            $hmacParams[] = self::BLOCK;
            // Else
        } else {
            // If submission time checks are enabled
            if ($this->_submissionMethodOrderEnabled()) {
                $hmacParams[] = $this->_method ?: strtoupper($_SERVER['REQUEST_METHOD']);
            }
            // If submission time checks are enabled
            if ($this->_submissionTimeEnabled()) {
                if ($this->_data) {
                    $hmacParams[] = true;
                }
                $hmacParams[] =
                $now = time();
            }
        }
        $hmac = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($hmacParams));
        $this->_debug('Creating HMAC for parameters', $hmacParams);
        $this->_debug('HMAC:', $hmac);

        return $hmac;
    }

    /**
     * Calculate the HMAC
     *
     * @param ServerRequestInterface $request Request
     * @param Antibot $antibot                Antibot instance
     * @param int|null $now                   Current timestamp
     *
     * @return string HMAC
     */
    protected function calculateHmac(ServerRequestInterface $request, Antibot $antibot, int &$now = null): string
    {
        $hmacParams = [$antibot->getUnique()];
        $now        = null;

        // Invalidate the HMAC if there's a current, invalid one
        if (false) {

        } else {
            $serverParams = $request->getServerParams();

            // If the request method vector should be used
            if (!empty($this->methodVector)) {
                $requestMethod = empty($serverParams['REQUEST_METHOD']) ? '' : $serverParams['REQUEST_METHOD'];
                $hmacParams[]  = $this->validateRequestMethod($requestMethod);
            }

            // If submission time checks are enabled
            if (!empty($this->submissionTimes)) {
                if (!empty($antibot->getData())) {
                    $hmacParams[] = true;
                }
                $hmacParams[] = $now = time();
            }
        }

//        print_r($hmacParams);

        $hmac = HmacFactory::createFromString(serialize($hmacParams), $antibot->getUnique());

        return $hmac;
    }
}
