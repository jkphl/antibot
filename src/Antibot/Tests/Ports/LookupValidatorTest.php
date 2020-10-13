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

use Jkphl\Antibot\Domain\Exceptions\BlacklistValidationException;
use Jkphl\Antibot\Domain\Exceptions\WhitelistValidationException;
use Jkphl\Antibot\Ports\LookupStrategy\ArrayLookupStrategy;
use Jkphl\Antibot\Ports\Validators\IpBlacklistValidator;
use Jkphl\Antibot\Ports\Validators\IpWhitelistValidator;
use Jkphl\Antibot\Ports\Validators\ParameterBlacklistValidator;
use Jkphl\Antibot\Tests\AbstractTestBase;

/**
 * Lookup Validator Tests
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Ports
 */
class LookupValidatorTest extends AbstractTestBase
{
    /**
     * Test the IP whitelist validator
     */
    public function testIpWhitelist(): void
    {
        $this->expectException(WhitelistValidationException::class);
        $request               = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $arrayWhitelist1       = new ArrayLookupStrategy(['4.3.2.1']);
        $ipWhitelistValidator1 = new IpWhitelistValidator($arrayWhitelist1);
        $this->assertTrue($ipWhitelistValidator1->validate($request, $this->createAntibot()));

        $arrayWhitelist2       = new ArrayLookupStrategy(['1.2.3.4']);
        $ipWhitelistValidator2 = new IpWhitelistValidator($arrayWhitelist2);
        $ipWhitelistValidator2->validate($request, $this->createAntibot());
    }

    /**
     * Test the IP blacklist validator
     */
    public function testIpBlacklist(): void
    {
        $this->expectException(BlacklistValidationException::class);
        $request               = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $arrayBlacklist1       = new ArrayLookupStrategy(['4.3.2.1']);
        $ipBlacklistValidator1 = new IpBlacklistValidator($arrayBlacklist1);
        $this->assertTrue($ipBlacklistValidator1->validate($request, $this->createAntibot()));

        $arrayBlacklist2       = new ArrayLookupStrategy(['1.2.3.4']);
        $ipBlacklistValidator2 = new IpBlacklistValidator($arrayBlacklist2);
        $ipBlacklistValidator2->validate($request, $this->createAntibot());
    }

    /**
     * Test the parameter blacklist validator with a GET parameter
     */
    public function testGetParamBlacklist(): void
    {
        $this->expectException(BlacklistValidationException::class);
        $request                  = $this->createRequest(
            ['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'],
            ['name' => 'John Doe', 'email' => 'test@example.com']
        );
        $arrayBlacklist1          = new ArrayLookupStrategy(['john@doe.com']);
        $paramBlacklistValidator1 = new ParameterBlacklistValidator($arrayBlacklist1, 'email');
        $this->assertTrue($paramBlacklistValidator1->validate($request, $this->createAntibot()));

        $arrayBlacklist2          = new ArrayLookupStrategy(['test@example.com']);
        $paramBlacklistValidator2 = new ParameterBlacklistValidator($arrayBlacklist2, 'email');
        $paramBlacklistValidator2->validate($request, $this->createAntibot());
    }

    /**
     * Test the parameter blacklist validator with a POST parameter
     */
    public function testPostParamBlacklist(): void
    {
        $this->expectException(BlacklistValidationException::class);
        $request                  = $this->createRequest(
            ['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'],
            [],
            ['name' => 'John Doe', 'email' => 'test@example.com']
        );
        $arrayBlacklist1          = new ArrayLookupStrategy(['john@doe.com']);
        $paramBlacklistValidator1 = new ParameterBlacklistValidator(
            $arrayBlacklist1,
            'email',
            ParameterBlacklistValidator::POST
        );
        $this->assertTrue($paramBlacklistValidator1->validate($request, $this->createAntibot()));

        $arrayBlacklist2          = new ArrayLookupStrategy(['test@example.com']);
        $paramBlacklistValidator2 = new ParameterBlacklistValidator(
            $arrayBlacklist2,
            'email',
            ParameterBlacklistValidator::POST
        );
        $paramBlacklistValidator2->validate($request, $this->createAntibot());
    }
}
