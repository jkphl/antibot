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

use Jkphl\Antibot\Domain\Antibot;
use Jkphl\Antibot\Domain\Contract\ValidatorInterface;
use Jkphl\Antibot\Domain\Exceptions\BlacklistValidationException;
use Jkphl\Antibot\Domain\Exceptions\ErrorException;
use Jkphl\Antibot\Domain\Exceptions\SkippedValidationException;
use Jkphl\Antibot\Domain\Exceptions\WhitelistValidationException;
use Jkphl\Antibot\Domain\Model\ValidationResult;
use Jkphl\Antibot\Tests\AbstractTestBase;
use Psr\Log\NullLogger;

/**
 * Antibot Tests
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Domain
 */
class AntibotTest extends AbstractTestBase
{
    /**
     * General test
     *
     * @expectedException Jkphl\Antibot\Domain\Exceptions\RuntimeException
     * @expectedExceptionCode 1544191654
     */
    public function testAntibot(): void
    {
        $session = md5(rand());
        $antibot = new Antibot($session, 'customPrefix');
        $this->assertInstanceOf(Antibot::class, $antibot);
        $this->assertEquals($antibot->getUnique(), $session);
        $this->assertEquals($antibot->getPrefix(), 'customPrefix');

        $logger = new NullLogger();
        $antibot->setLogger($logger);
        $this->assertInstanceOf(NullLogger::class, $antibot->getLogger());

        $antibot->getParameterPrefix();
    }

    /**
     * Test uninitialized data
     *
     * @expectedException Jkphl\Antibot\Domain\Exceptions\RuntimeException
     * @expectedExceptionCode 1544191654
     */
    public function testUnitializedData(): void
    {
        $antibot = new Antibot(md5(rand()));

        $antibot->getData();
    }

    /**
     * Test the skip validator functionality
     */
    public function testSkipValidator(): void
    {
        $antibot   = new Antibot(md5(rand()));
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->will($this->throwException(new SkippedValidationException('skipped')));
        $antibot->addValidator($validator);
        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertFalse($validationResult->isValid());
        $this->assertFalse($validationResult->isFailed());
        $this->assertTrue($validationResult->isSkipped());
        $this->assertTrue($validationResult->hasSkips());
    }

    /**
     * Test the blacklist validator functionality
     */
    public function testBlacklistValidator(): void
    {
        $antibot   = new Antibot(md5(rand()));
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->will($this->throwException(new BlacklistValidationException('blacklist')));
        $antibot->addValidator($validator);
        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertFalse($validationResult->isValid());
        $this->assertTrue($validationResult->isFailed());
        $this->assertTrue($validationResult->isBlacklisted());
        $this->assertEquals(['blacklist'], $validationResult->getBlacklists());
    }

    /**
     * Test the whitelist validator functionality
     */
    public function testWhitelistValidator(): void
    {
        $antibot   = new Antibot(md5(rand()));
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->will($this->throwException(new WhitelistValidationException('whitelist')));
        $antibot->addValidator($validator);
        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertTrue($validationResult->isValid());
        $this->assertFalse($validationResult->isFailed());
        $this->assertTrue($validationResult->isWhitelisted());
        $this->assertEquals(['whitelist'], $validationResult->getWhitelists());
    }

    /**
     * Test the erroring validator functionality
     */
    public function testErroringValidator(): void
    {
        $error     = time();
        $antibot   = new Antibot(md5(rand()));
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->will($this->throwException(new ErrorException('error', $error)));
        $antibot->addValidator($validator);
        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertFalse($validationResult->isValid());
        $this->assertTrue($validationResult->isFailed());
        $this->assertTrue($validationResult->hasErrors());
        $errors = $validationResult->getErrors();
        $this->assertTrue(is_array($errors));
        $this->assertEquals(1, count($errors));
        $this->assertInstanceOf(ErrorException::class, $errors[0]);
        $this->assertEquals($error, $errors[0]->getCode());
    }

    /**
     * Test the failing validator functionality
     */
    public function testFailingValidator(): void
    {
        $error     = time();
        $antibot   = new Antibot(md5(rand()));
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(false);
        $antibot->addValidator($validator);
        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertFalse($validationResult->isValid());
        $this->assertTrue($validationResult->isFailed());
        $this->assertFalse($validationResult->hasErrors());
    }

    /**
     * Test the succeeding validator functionality
     */
    public function testSucceedingValidator(): void
    {
        $error     = time();
        $antibot   = new Antibot(md5(rand()));
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(true);
        $antibot->addValidator($validator);
        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertTrue($validationResult->isValid());
        $this->assertFalse($validationResult->isFailed());
        $this->assertFalse($validationResult->hasErrors());
    }
}
