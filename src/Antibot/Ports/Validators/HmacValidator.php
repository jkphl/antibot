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
     * @var null|array
     */
    protected $methodVector = null;
    /**
     * Request submission times
     *
     * @var null|array
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
    const MINIMUM_SUBMISSION = 3;
    /**
     * Minimum submission time for follow-up submissions
     *
     * @var float
     */
    const MINIMUM_FOLLOWUP_SUBMISSION = 1;
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
     * @throws HmacValidationException If the request timing is invalid
     */
    protected function validateHmac(string $hmac, ServerRequestInterface $request, Antibot $antibot): bool
    {
//        $previousMethod = null;
        $hmacParams = [$antibot->getUnique()];

        // Short-circuit blocked HMAC
        $hmacBlock   = $hmacParams;
        $hmacBlock[] = self::BLOCK;
        if (HmacFactory::createFromString(serialize($hmacBlock), $antibot->getUnique()) === $hmac) {
            return false;
        }

        // Validate the request method vector
        $this->validateRequestMethodVector($request, $hmacParams);

        // If the request timings validate
        if ($this->validateRequestTiming($hmac, $antibot, $hmacParams)) {
            return true;
        }

        // Else: Do a simple validation without request timings
        $currentHMAC = HmacFactory::createFromString(serialize($hmacParams), $antibot->getUnique());

        return $hmac === $currentHMAC;
    }

    /**
     * Validate the request method vector
     *
     * @param ServerRequestInterface $request Request
     * @param array $hmacParams               HMAC parameters
     *
     * @throws HmacValidationException If the request method order is invalid
     */
    protected function validateRequestMethodVector(ServerRequestInterface $request, array &$hmacParams): void
    {
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
    }

    /**
     * Validate the request timing
     *
     * @param string $hmac      HMAC
     * @param Antibot $antibot  Antibot instance
     * @param array $hmacParams HMAC parameters
     *
     * @return bool Request timings were enabled and validated successfully
     *
     * @throws HmacValidationException If the request timing is invalid
     */
    protected function validateRequestTiming(string $hmac, Antibot $antibot, array $hmacParams): bool
    {
        // If submission time checks are enabled
        if (!empty($this->submissionTimes)) {
            list($first, $min, $max) = $this->submissionTimes;
            $now       = time();
            $initial   = $now - $first;
            $data      = $antibot->getData();
            $timestamp = empty($data['ts']) ? null : $data['ts'];

            // If a timestamp has been submitted
            if ($timestamp
                && (($timestamp + $min) <= $now)
                && (($timestamp + $max) >= $now)
                && $this->probeTimedHmacAsInitialAndFollowup($hmac, $antibot, $hmacParams, $timestamp, $initial)
            ) {
                $antibot->getLogger()->debug("[HMAC] Validated using submitted timestamp $timestamp");

                return true;
            } else {
                // Run through the valid seconds range
                for ($time = $now - $min; $time >= $now - $max; --$time) {
                    // If the HMAC validates as initial or follow-up request
                    if ($this->probeTimedHmacAsInitialAndFollowup($hmac, $antibot, $hmacParams, $time, $initial)) {
                        return true;
                    }
                }
            }

            throw new HmacValidationException(
                HmacValidationException::INVALID_REQUEST_TIMING_STR,
                HmacValidationException::INVALID_REQUEST_TIMING
            );
        }

        return false;
    }

    /**
     * Probe a timed HMAC both as initial and follow-up request
     *
     * @param string $hmac      HMAC
     * @param Antibot $antibot  Antibot instance
     * @param array $hmacParams HMAC params
     * @param int $timestamp    Timestamp
     * @param int $initial      Initial request threshold
     *
     * @return bool HMAC is valid
     */
    protected function probeTimedHmacAsInitialAndFollowup(
        string $hmac,
        Antibot $antibot,
        array $hmacParams,
        int $timestamp,
        int $initial
    ): bool {
        // If the HMAC validates with auto-guessed mode: Succeed
        if ($this->probeTimedHmac($hmac, $antibot, $hmacParams, $timestamp, $timestamp > $initial)) {
            return true;
        }

        // Also test as late follow-up request
        if (($timestamp <= $initial) && $this->probeTimedHMAC($hmac, $antibot, $hmacParams, $timestamp, true)) {
            return true;
        }

        return false;
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

        $antibot->getLogger()->debug("[HMAC] Probing $timestamp (".($followUp ? 'FLLW' : 'INIT')."): $currentHMAC");

        return $currentHMAC == $hmac;
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
            $hmacParams[] = self::BLOCK;
        } else {
            $this->calculateRequestMethodVectorHmac($request, $hmacParams);
            $this->calculateRequestTimingHmac($antibot, $hmacParams, $now);
        }

        $hmac = HmacFactory::createFromString(serialize($hmacParams), $antibot->getUnique());

        $antibot->getLogger()->debug("[HMAC] Created HMAC $hmac", $hmacParams);

        return $hmac;
    }

    /**
     * Add request method vector data to the HMAC configuration
     *
     * @param ServerRequestInterface $request Request
     * @param array $hmacParams               HMAC parameters
     */
    protected function calculateRequestMethodVectorHmac(ServerRequestInterface $request, array &$hmacParams): void
    {
        // If the request method vector should be used
        if (!empty($this->methodVector)) {
            $serverParams  = $request->getServerParams();
            $requestMethod = empty($serverParams['REQUEST_METHOD']) ? '' : $serverParams['REQUEST_METHOD'];
            $hmacParams[]  = $this->validateRequestMethod($requestMethod);
        }
    }

    /**
     * Add request timing data to the HMAC configuration
     *
     * @param Antibot $antibot  Antibot instance
     * @param array $hmacParams HMAC parameters
     * @param int|null $now     Current timestamp
     */
    protected function calculateRequestTimingHmac(Antibot $antibot, array &$hmacParams, int &$now = null): void
    {
        // If submission time checks are enabled
        if (!empty($this->submissionTimes)) {
            if (!empty($antibot->getData())) {
                $hmacParams[] = true;
            }
            $hmacParams[] = $now = time();
        }
    }
}
