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

use Jkphl\Antibot\Ports\Antibot;
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
     * Test the armoring
     */
    public function testArmor(): void
    {
        $sessionId     = md5(rand());
        $antibot       = new Antibot([], $sessionId);
        $request       = $this->createRequest(
            ['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'],
            ['name' => 'John Doe', 'email' => 'test@example.com']
        );
        $hmacValidator = new HmacValidator();
        $hmacValidator->setMethodVector(HmacValidator::METHOD_GET, HmacValidator::METHOD_POST);
        $hmacValidator->setSubmissionTimes(1800, 10, 3);
        $antibot->addValidator($hmacValidator);
        echo $armor = $antibot->armor($request);
        $this->assertTrue(strlen($armor) > 0);
    }
}
