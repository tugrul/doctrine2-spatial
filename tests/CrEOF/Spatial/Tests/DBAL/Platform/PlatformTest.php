<?php
/**
 * Copyright (C) 2015 Derek J. Lambert
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CrEOF\Spatial\Tests\DBAL\Platform;

use CrEOF\Spatial\Exception\UnsupportedPlatformException;
use CrEOF\Spatial\Tests\OrmMockTestCase;
use Doctrine\DBAL\Types\Type;

use Doctrine\ORM\Tools\SchemaTool;
use CrEOF\Spatial\DBAL\Types\Geometry\PointType;
use CrEOF\Spatial\Tests\Fixtures\PointEntity;

/**
 * Spatial platform tests
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 *
 * @group geometry
 */
class PlatformTest extends OrmMockTestCase
{
    public function setUp()
    {
        if (! Type::hasType('point')) {
            Type::addType('point', PointType::class);
        }

        parent::setUp();
    }

    public function testUnsupportedPlatform()
    {
        $this->expectException(UnsupportedPlatformException::class);
        $this->expectExceptionMessage('DBAL platform "YourSQL" is not currently supported.');

        $metadata   = $this->getMockEntityManager()->getClassMetadata(PointEntity::class);
        $schemaTool = new SchemaTool($this->getMockEntityManager());

        $schemaTool->createSchema(array($metadata));
    }
}
