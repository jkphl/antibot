<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests
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

namespace Jkphl\Antibot\Tests;

use Jkphl\Antibot\Ports\Antibot;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract PHPUnit Test Base
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Tests
 */
class AbstractTestBase extends TestCase
{
    /**
     * Create and return a server request
     *
     * @param array $server $_SERVER data
     * @param array $get    $_GET data
     * @param array $post   $_POST data
     *
     * @return ServerRequestInterface Server request
     */
    protected function createRequest(array $server, array $get = [], array $post = []): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $creator      = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        return $creator->fromArrays(
            $server, // $_SERVER
            [], // Headers
            [], // Cookies
            $get, // GET
            $post, // POST
            [], // FILES
            null // Body
        );
    }

    /**
     * Create an Antibot instance
     *
     * @param string|null $session Session-persistent unique ID
     *
     * @return Antibot Antibot instance
     * @throws \Exception
     */
    protected function createAntibot(string &$session = null): Antibot
    {
        if ($session === null) {
            $session = md5(rand());
        }

        $antibot = new Antibot($session, Antibot::DEFAULT_PREFIX);

        $log = new Logger('ANTIBOT');
        $log->pushHandler(new StreamHandler('php://stdout'));
        $antibot->setLogger($log);

        return $antibot;
    }
}
