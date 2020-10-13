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

use Jkphl\Antibot\Infrastructure\Model\InputElement;
use Jkphl\Antibot\Ports\Validators\HoneypotValidator;
use Jkphl\Antibot\Tests\AbstractTestBase;

/**
 * Honeypot Validator Test
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests\Ports
 */
class HoneypotValidatorTest extends AbstractTestBase
{
    /**
     * Test the honeypots
     */
    public function testHoneypots(): void
    {
        $renderer          = function (InputElement $input, string $html) {
            return '<label style="display:none">' . $html . '</label>';
        };
        $antibot           = $this->createAntibot();
        $honeypotValidator = new HoneypotValidator(['email' => 'email', 'personal' => ['name' => 'text']], $renderer);
        $antibot->addValidator($honeypotValidator);

        // Run a first validation: Should be skipped
        $request1         = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4']);
        $validationResult = $antibot->validate($request1);
        $this->assertTrue($validationResult->isValid());

        $armor = $antibot->armorInputs($request1);
        $this->assertTrue(is_array($armor));
        $this->assertEquals(2, count($armor));
        $this->assertEquals($antibot->getParameterPrefix() . '[email]', $armor[0]->getAttributes()['name']);
        $this->assertEquals(0, strlen($armor[0]->getAttributes()['value']));
        $post = $this->getArmorParams($armor);

        // Empty honeypots should succeed
        $request2         = $this->createRequest(['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => '1.2.3.4'], [], $post);
        $validationResult = $antibot->validate($request2);
        $this->assertTrue($validationResult->isValid());

        // A non-empty honeypot should fail
        $post[$antibot->getParameterPrefix()]['personal']['name'] = 'John Doe';
        $request3                                                 = $this->createRequest(
            [
                'REQUEST_METHOD' => 'GET',
                'REMOTE_ADDR'    => '1.2.3.4'
            ],
            [],
            $post
        );
        $validationResult                                         = $antibot->validate($request3);
        $this->assertTrue($validationResult->isFailed());
    }
}
