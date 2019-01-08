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
use Jkphl\Antibot\Infrastructure\Exceptions\HoneypotValidationException;
use Jkphl\Antibot\Infrastructure\Model\AbstractValidator;
use Jkphl\Antibot\Infrastructure\Model\InputElement;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Honeypot Validator
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Ports\Validators
 */
class HoneypotValidator extends AbstractValidator
{
    /**
     * Honeypot field names & structure
     *
     * @var array
     */
    protected $honeypots;
    /**
     * Field renderer
     *
     * @var \Closure
     */
    protected $renderer;
    /**
     * Validation order position
     *
     * @var int
     */
    const POSITION = 50;

    /**
     * Constructor
     *
     * @param array $honeypots   Honeypot field names & structure
     * @param \Closure $renderer Field renderer
     */
    public function __construct(array $honeypots, \Closure $renderer = null)
    {
        $this->honeypots = $honeypots;
        $this->renderer  = $renderer;
    }

    /**
     * Validate a request
     *
     * @param ServerRequestInterface $request Request
     * @param Antibot $antibot                Antibot instance
     *
     * @return bool Success
     * @throws HoneypotValidationException If a honeypot was triggered
     */
    public function validate(ServerRequestInterface $request, Antibot $antibot): bool
    {
        $data = array_merge((array)$request->getQueryParams(), (array)$request->getParsedBody());

        return $this->validateHoneypotsRecursive($antibot->getScopedParameters($this->honeypots), $data, $antibot);
    }

    /**
     * Recursively validate honeypots
     *
     * @param array $honeypots Honeypot configuration
     * @param array $data      Submitted data
     * @param Antibot $antibot Antibot instance
     * @param null $prefix     Variable prefix
     *
     * @return bool Success
     * @throws HoneypotValidationException If a honeypot was triggered
     */
    protected function validateHoneypotsRecursive(array $honeypots, array $data, Antibot $antibot, $prefix = null): bool
    {
        // Run through the honeypot configuration
        foreach ($honeypots as $name => $config) {
            if (is_array($config)) {
                $honeypotPrefix = $prefix ? $prefix.'['.htmlspecialchars($name).']' : htmlspecialchars($name);
                if (array_key_exists($name, $data)) {
                    $this->validateHoneypotsRecursive($config, $data[$name], $antibot, $honeypotPrefix);
                }
                continue;
            }

            // If the honeypot was submitted empty (or not submitted at all): Succeed
            if (!array_key_exists($name, $data) || !strlen($data[$name])) {
                continue;
            }

            $honeypotName = $prefix ? $prefix.'['.htmlspecialchars($name).']' : htmlspecialchars($name);
            $antibot->getLogger()->debug('[HNPT] Triggered honeypot "'.$honeypotName.'"');
            throw new HoneypotValidationException(
                sprintf(HoneypotValidationException::TRIGGERED_HONEYPOT_STR, $honeypotName),
                HoneypotValidationException::TRIGGERED_HONEYPOT
            );
        }

        return true;
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
        $armor = [];
        $this->createHoneypotsRecursive($antibot->getScopedParameters($this->honeypots), $armor);

        return $armor;
    }

    /**
     * Recursively create honeypot input elements
     *
     * @param array $honeypots Honeypot configuration
     * @param array $armor     Armor input elements
     * @param null $prefix     Variable prefix
     */
    protected function createHoneypotsRecursive(array $honeypots, array &$armor, $prefix = null): void
    {
        // Run through the honeypot configuration
        foreach ($honeypots as $name => $config) {
            if (is_array($config)) {
                $honeypotPrefix = $prefix ? $prefix.'['.htmlspecialchars($name).']' : htmlspecialchars($name);
                $this->createHoneypotsRecursive($config, $armor, $honeypotPrefix);
                continue;
            }

            $honeypotName = $prefix ? $prefix.'['.htmlspecialchars($name).']' : htmlspecialchars($name);
            $armor[]      = new InputElement([
                'type'  => $config ?: 'text',
                'name'  => $honeypotName,
                'value' => ''
            ], $this->renderer);
        }
    }

    /**
     * Return all serializable properties
     *
     * The renderer closure must be omitted in order to make the validator serializable
     *
     * @return string[] Serializable properties
     */
    public function __sleep()
    {
        return ['honeypots'];
    }
}
