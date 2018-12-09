<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Ports
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

namespace Jkphl\Antibot\Tests\Ports;

use Jkphl\Antibot\Infrastructure\Exceptions\HmacValidationException;
use Jkphl\Antibot\Ports\Validators\HmacValidator;
use Jkphl\Antibot\Tests\AbstractTestBase;

/**
 * HMAC Validator Test
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Ports
 */
class HmacValidatorTest extends AbstractTestBase
{
    /**
     * Protected function test the general HMAC validation
     *
     */
    public function testGeneralValidation(): void
    {
        $session       = md5(rand());
        $antibot       = $this->createAntibot($session);
        $hmacValidator = new HmacValidator();
        $antibot->addValidator($hmacValidator);

        // Run a first validation: Should be skipped
        $request1         = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request1);
        $this->assertFalse($validationResult->isValid());
        $this->assertFalse($validationResult->isFailed());
        $this->assertTrue($validationResult->isSkipped());

        // Arm & run a second request: Should succeed
        $armor            = $antibot->armorInputs($request1);
        $post             = $this->getArmorParams($armor);
        $request2         = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request2);
        $this->assertTrue($validationResult->isValid());
    }

    /**
     * Protected function test the HMAC request method order validation
     */
    public function testRequestMethodOrderValidation(): void
    {
        $antibot       = $this->createAntibot();
        $hmacValidator = new HmacValidator();
        $hmacValidator->setMethodVector(HmacValidator::METHOD_GET, HmacValidator::METHOD_POST);
        $antibot->addValidator($hmacValidator);
        $request1 = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $armor    = $antibot->armorInputs($request1);
        $post     = $this->getArmorParams($armor);

        // Second request
        $request2         = $this->createRequest(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request2);
        $this->assertTrue($validationResult->isValid());

        // Third request using the wrong request method
        $request3         = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'], $post);
        $validationResult = $antibot->validate($request3);
        $this->assertFalse($validationResult->isValid());
        $this->assertTrue($validationResult->hasErrors());
        $errors = $validationResult->getErrors();
        $this->assertEquals(1, count($errors));
        $this->assertInstanceOf(HmacValidationException::class, $errors[0]);
        $this->assertEquals(1544292604, $errors[0]->getCode());
    }

    /**
     * Protected function test the HMAC request timing validation
     */
    public function testRequestTimingValidation(): void
    {
        $session       = md5(rand());
        $antibot       = $this->createAntibot($session);
        $hmacValidator = new HmacValidator();
        $hmacValidator->setSubmissionTimes(10, 5, 1);
        $antibot->addValidator($hmacValidator);
        $request1 = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $this->assertTrue($antibot->validate($request1)->isSkipped());

        // Create the armor for a second request, including submission time limits
        $armor = $antibot->armorInputs($request1);
        $post  = $this->getArmorParams($armor);

        // A second call after only 1 second should fail validation
        sleep(1);
        $request2         = $this->createRequest(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request2);
        $this->assertTrue($validationResult->isFailed());

        // Waiting for 4 more seconds should succeed
        sleep(5);
        $request3         = $this->createRequest(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request3);
        $this->assertTrue($validationResult->isValid());

        // Rearm, wait for 1 second and retry: Should succeed as it's a follow-up request
        $armor = $antibot->armorInputs($request3);
        $post  = $this->getArmorParams($armor);
        sleep(1);
        $request4         = $this->createRequest(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request4);
        $this->assertTrue($validationResult->isValid());

        // Wait for another 10 seconds and retry: Should fail as it exceeded the maximum time
        sleep(10);
        $request5         = $this->createRequest(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request5);
        $this->assertTrue($validationResult->isFailed());
        $this->assertTrue($validationResult->hasErrors());
        $errors = $validationResult->getErrors();
        $this->assertEquals(1, count($errors));
        $this->assertInstanceOf(HmacValidationException::class, $errors[0]);
        $this->assertEquals(1544292684, $errors[0]->getCode());
    }

    /**
     * Test the armoring
     */
    public function testArmor(): void
    {
        $sessionId     = md5(rand());
        $antibot       = $this->createAntibot($sessionId);
        $request       = $this->createRequest(
            ['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'],
            ['name' => 'John Doe', 'email' => 'test@example.com']
        );
        $hmacValidator = new HmacValidator();
        $hmacValidator->setMethodVector(HmacValidator::METHOD_GET, HmacValidator::METHOD_POST);
        $hmacValidator->setSubmissionTimes(1800, 10, 3);
        $antibot->addValidator($hmacValidator);
        $armor = $antibot->armorInputs($request);
        $this->assertTrue(is_array($armor));
        $this->assertEquals(2, count($armor));
        $this->assertEquals(40, strlen($armor[0]->getAttributes()['value']));
        $this->assertTrue(is_int($armor[1]->getAttributes()['value']));
    }
}
