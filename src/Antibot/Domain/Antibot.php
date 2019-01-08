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

use Jkphl\Antibot\Domain\Contract\ValidatorInterface;
use Jkphl\Antibot\Domain\Exceptions\BlacklistValidationException;
use Jkphl\Antibot\Domain\Exceptions\ErrorException;
use Jkphl\Antibot\Domain\Exceptions\InvalidArgumentException;
use Jkphl\Antibot\Domain\Exceptions\RuntimeException;
use Jkphl\Antibot\Domain\Exceptions\SkippedValidationException;
use Jkphl\Antibot\Domain\Exceptions\WhitelistValidationException;
use Jkphl\Antibot\Domain\Model\ValidationResult;
use Jkphl\Antibot\Infrastructure\Model\InputElement;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Antibot core
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Domain
 */
class Antibot implements LoggerAwareInterface
{
    /**
     * Session persistent, unique token
     *
     * @var string
     */
    protected $unique;
    /**
     * Antibot prefix
     *
     * @var string
     */
    protected $prefix;
    /**
     * Parameter scope nodes
     *
     * @var array
     */
    protected $scope = [];
    /**
     * Unique signature
     *
     * @var string
     */
    protected $signature;
    /**
     * Parameter prefix
     *
     * @var string
     */
    protected $parameterPrefix;
    /**
     * GET & POST data
     *
     * @var null|array
     */
    protected $data = null;
    /**
     * Validators
     *
     * @var ValidatorInterface[]
     */
    protected $validators = [];
    /**
     * Immutable instance
     *
     * @var bool
     */
    protected $immutable = false;
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger = null;
    /**
     * Default antibot prefix
     *
     * @var string
     */
    const DEFAULT_PREFIX = 'antibot';

    /**
     * Antibot constructor
     *
     * @param string $unique Session-persistent, unique key
     * @param string $prefix Prefix
     */
    public function __construct(string $unique, string $prefix = self::DEFAULT_PREFIX)
    {
        $this->unique = $unique;
        $this->prefix = $prefix;
        $this->logger = new NullLogger();
    }

    /**
     * Return the session persistent, unique token
     *
     * @return string Session persistent, unique token
     */
    public function getUnique(): string
    {
        return $this->unique;
    }

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
     * Return the submitted Antibot data
     *
     * @return string[] Antibot data
     */
    public function getData(): ?array
    {
        $this->checkInitialized();

        return $this->data;
    }

    /**
     * Return the parameter prefix
     *
     * @return string Parameter prefix
     * @throws RuntimeException If Antibot needs to be initialized
     */
    public function getParameterPrefix(): string
    {
        $this->checkInitialized();

        return $this->parameterPrefix;
    }

    /**
     * Add a validator
     *
     * @param ValidatorInterface $validator Validator
     */
    public function addValidator(ValidatorInterface $validator): void
    {
        $this->checkImmutable();
        $this->validators[] = $validator;
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
        $this->logger->info('Start validation');
        $this->initialize($request);
        $result = new ValidationResult();

        // Run through all validators (in order)
        /** @var ValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            try {
                if (!$validator->validate($request, $this)) {
                    $result->setValid(false);
                }

                // If the validator skipped validation
            } catch (SkippedValidationException $e) {
                $result->addSkip($e->getMessage());

                // If the request failed a blacklist test
            } catch (BlacklistValidationException $e) {
                $result->addBlacklist($e->getMessage());
                $result->setValid(false);

                // If the request passed a whitelist test
            } catch (WhitelistValidationException $e) {
                $result->addWhitelist($e->getMessage());
                break;

                // If an error occured
            } catch (ErrorException $e) {
                $result->addError($e);
                $result->setValid(false);
            }
        }

        $this->logger->info('Finished validation');

        return $result;
    }

    /**
     * Create and return the raw armor input elements
     *
     * @param ServerRequestInterface $request Request
     *
     * @return InputElement[] Armor input elements
     */
    public function armorInputs(ServerRequestInterface $request): array
    {
        $this->initialize($request);
        $armor = [];

        // Run through all validators (in order)
        /** @var ValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            $validatorArmor = $validator->armor($request, $this);
            if (!empty($validatorArmor)) {
                $armor = array_merge($armor, $validatorArmor);
            }
        }

        return $armor;
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

    /**
     * Pre-validation initialization
     *
     * @param ServerRequestInterface $request Request
     */
    protected function initialize(ServerRequestInterface $request): void
    {
        if (!$this->immutable) {
            $this->immutable = true;
            usort($this->validators, [$this, 'sortValidators']);
            $this->signature       = $this->calculateSignature();
            $this->parameterPrefix = $this->prefix.'_'.$this->signature;
        }
        $this->extractData($request);
    }

    /**
     * Calculate the unique signature
     *
     * @return string Signature
     */
    protected function calculateSignature(): string
    {
        $params = [$this->prefix, $this->validators];

        return sha1($this->unique.serialize($params));
    }

    /**
     * Extract the antibot data from GET and POST parameters
     *
     * @param ServerRequestInterface $request Request
     */
    protected function extractData(ServerRequestInterface $request): void
    {
        $get        = $this->extractScopedData($request->getQueryParams() ?? []);
        $post       = $this->extractScopedData($request->getParsedBody() ?? []);
        $this->data = (($get !== null) || ($post !== null)) ? array_merge((array)$get, (array)$post) : null;
    }

    /**
     * Extract scoped data
     *
     * @param array $data Source data
     *
     * @return array|null Scoped data
     */
    protected function extractScopedData(array $data): ?array
    {
        // Run through all scope nodes
        foreach (array_merge($this->scope, [$this->getParameterPrefix()]) as $node) {
            if (!isset($data[$node])) {
                return null;
            }

            $data = $data[$node];
        }

        return $data;
    }

    /**
     * Check whether this Antibot instance is immutable
     *
     * @throws RuntimeException If the Antibot instance is immutable
     */
    protected function checkImmutable(): void
    {
        if ($this->immutable) {
            throw new RuntimeException(
                RuntimeException::ANTIBOT_IMMUTABLE_STR,
                RuntimeException::ANTIBOT_IMMUTABLE
            );
        }
    }

    /**
     * Check whether this Antibot instance is already initialized
     *
     * @throws RuntimeException If the Antibot instance still needs to be initialized
     */
    protected function checkInitialized(): void
    {
        // If the Antibot instance still needs to be initialized
        if (!$this->immutable) {
            throw new RuntimeException(
                RuntimeException::ANTIBOT_INITIALIZE_STR,
                RuntimeException::ANTIBOT_INITIALIZE
            );
        }
    }

    /**
     * Return the logger
     *
     * @return LoggerInterface Logger
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger Logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Set the parameter scope
     *
     * @param string[] ...$scope Parameter scope
     */
    public function setParameterScope(...$scope): void
    {
        // Run through all scope nodes
        foreach ($scope as $node) {
            if (!is_string($node) || empty($node)) {
                throw new InvalidArgumentException(
                    sprintf(InvalidArgumentException::INVALID_SCOPE_NODE_STR, $node),
                    InvalidArgumentException::INVALID_SCOPE_NODE
                );
            }

            $this->scope[] = $node;
        }
    }

    /**
     * Scope a set of parameters
     *
     * @param array $params Parameters
     *
     * @return array Scoped parameters
     */
    public function getScopedParameters(array $params): array
    {
        $params = [$this->getParameterPrefix() => $params];
        $scope  = $this->scope;
        while ($node = array_pop($scope)) {
            $params = [$node => $params];
        }

        return $params;
    }
}
