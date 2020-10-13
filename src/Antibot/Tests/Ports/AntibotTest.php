<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Ports
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

namespace Jkphl\Antibot\Tests\Ports;

use Jkphl\Antibot\Ports\Antibot;
use Jkphl\Antibot\Ports\Exceptions\InvalidArgumentException;
use Jkphl\Antibot\Ports\LookupStrategy\ArrayLookupStrategy;
use Jkphl\Antibot\Ports\Validators\IpWhitelistValidator;
use Jkphl\Antibot\Tests\AbstractTestBase;

/**
 * Antibot Test
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Ports
 */
class AntibotTest extends AbstractTestBase
{
    /**
     * Test the prefix
     */
    public function testPrefix()
    {
        $this->expectExceptionCode(1544118177);
        $this->expectException(InvalidArgumentException::class);

        $antibot = $this->createAntibot();
        $this->assertInstanceOf(Antibot::class, $antibot);
        $this->assertEquals(Antibot::DEFAULT_PREFIX, $antibot->getPrefix());

        $randomPrefix = md5(microtime(true));
        $antibot->setPrefix($randomPrefix);
        $this->assertEquals($antibot->getPrefix(), $randomPrefix);

        $antibot->setPrefix('');
    }

    /**
     * Test the validation with some basic validators
     */
    public function testValidation()
    {
        $antibot = $this->createAntibot();
        $this->assertInstanceOf(Antibot::class, $antibot);

        // Add a whitelist validator
        $arrayWhitelist       = new ArrayLookupStrategy(['1.2.3.4']);
        $ipWhitelistValidator = new IpWhitelistValidator($arrayWhitelist);
        $antibot->addValidator($ipWhitelistValidator);

        $request          = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request);
        $this->assertTrue($validationResult->isValid());
        $this->assertTrue($validationResult->isWhitelisted());
    }
}
