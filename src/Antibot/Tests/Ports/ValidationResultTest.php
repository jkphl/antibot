<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Domain
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

namespace Jkphl\Antibot\Tests\Domain;

use Jkphl\Antibot\Domain\Exceptions\ErrorException;
use Jkphl\Antibot\Ports\ValidationResult;
use Jkphl\Antibot\Tests\AbstractTestBase;

/**
 * Validation Result Test
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Domain
 */
class ValidationResultTest extends AbstractTestBase
{
    /**
     * Test the validation result
     */
    public function testValidationResult(): void
    {
        $validationResult = new ValidationResult();
        $this->assertTrue($validationResult->isValid());
        $this->assertFalse($validationResult->isFailed());
        $this->assertFalse($validationResult->isSkipped());
        $this->assertFalse($validationResult->isWhitelisted());
        $this->assertFalse($validationResult->isBlacklisted());
        $this->assertFalse($validationResult->hasErrors());
        $this->assertFalse($validationResult->hasSkips());

        $now = time();
        $validationResult->setValid(false);
        $validationResult->addWhitelist('whitelist');
        $validationResult->addBlacklist('blacklist');
        $validationResult->addError(new ErrorException('error', $now));
        $validationResult->addSkip('skip');
        $this->assertFalse($validationResult->isValid());
        $this->assertFalse($validationResult->isFailed());
        $this->assertTrue($validationResult->isSkipped());
        $this->assertEquals(['whitelist'], $validationResult->getWhitelists());
        $this->assertEquals(['blacklist'], $validationResult->getBlacklists());
        $errors = $validationResult->getErrors();
        $this->assertTrue(is_array($errors));
        $this->assertEquals(1, count($errors));
        $this->assertInstanceOf(ErrorException::class, $errors[0]);
        $this->assertEquals($now, $errors[0]->getCode());
    }
}
