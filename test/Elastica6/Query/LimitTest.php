<?php
namespace Elastica6\Test\Query;

use Elastica6\Query\Limit;
use Elastica6\Test\Base as BaseTest;

class LimitTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testSetType()
    {
        $query = new Limit(10);
        $this->assertEquals(10, $query->getParam('value'));

        $this->assertInstanceOf(Limit::class, $query->setLimit(20));
        $this->assertEquals(20, $query->getParam('value'));
    }

    /**
     * @group unit
     */
    public function testToArray()
    {
        $query = new Limit(15);

        $expectedArray = [
            'limit' => ['value' => 15],
        ];

        $this->assertEquals($expectedArray, $query->toArray());
    }
}
