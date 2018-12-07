<?php

/**
 * antibot
 *
 * @category   Jkphl
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Factory
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

namespace Jkphl\Antibot\Infrastructure\Factory;

/**
 * HMAC factory
 *
 * @package    Jkphl\Antibot
 * @subpackage Jkphl\Antibot\Infrastructure\Factory
 */
class HmacFactory
{
    /**
     * Returns a proper HMAC on a given input string and encryption key.
     *
     * @param string $input  Input string to create HMAC from
     * @param string $secret Secret to prevent hmac being used in a different context
     *
     * @return string resulting (hexadecimal) HMAC currently with a length of 40 (HMAC-SHA-1)
     */
    public static function createFromString(string $input, $secret = ''): string
    {
        $hashAlgorithm = 'sha1';
        $hashBlocksize = 64;
        if (extension_loaded('hash')
            && function_exists('hash_hmac')
            && function_exists('hash_algos')
            && in_array($hashAlgorithm, hash_algos())) {
            $hmac = hash_hmac($hashAlgorithm, $input, $secret);
        } else {
            $opad = str_repeat(chr(92), $hashBlocksize);
            $ipad = str_repeat(chr(54), $hashBlocksize);
            if (strlen($secret) > $hashBlocksize) {
                $key = str_pad(pack('H*', call_user_func($hashAlgorithm, $secret)), $hashBlocksize, chr(0));
            } else {
                $key = str_pad($secret, $hashBlocksize, chr(0));
            }
            $hmac = call_user_func($hashAlgorithm,
                ($key ^ $opad).pack('H*', call_user_func($hashAlgorithm, (($key ^ $ipad).$input))));
        }

        return $hmac;
    }
}
