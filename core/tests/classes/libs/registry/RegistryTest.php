<?php
/**
 * KumbiaPHP web & app Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @category   Test
 * @package    Registry
 *
 * @copyright  Copyright (c) 2005 - 2026 KumbiaPHP Team (http://www.kumbiaphp.com)
 * @license    https://github.com/KumbiaPHP/KumbiaPHP/blob/master/LICENSE   New BSD License
 */

/**
 * @category    Test
 * @package     Registry
 * @runTestsInSeparateProcesses
 */
class RegistryTest extends PHPUnit\Framework\TestCase
{
    public function testGetReturnsNullForMissingKey()
    {
        $this->assertNull(Registry::get('missing'));
    }

    public function testSetAndGet()
    {
        Registry::set('k', 'v');
        $this->assertSame(['v'], Registry::get('k'));
    }

    public function testAppend()
    {
        Registry::set('k', 'v1');
        Registry::append('k', 'v2');
        $this->assertSame(['v1', 'v2'], Registry::get('k'));
    }

    public function testPrepend()
    {
        Registry::set('k', 'v1');
        Registry::prepend('k', 'v2');
        $this->assertSame(['v2', 'v1'], Registry::get('k'));
    }

    public function testAppendWithoutSet()
    {
        Registry::append('k', 'v');
        $this->assertSame(['v'], Registry::get('k'));
    }

    public function testPrependWithoutSet()
    {
        Registry::prepend('k', 'v');
        $this->assertSame(['v'], Registry::get('k'));
    }
}
