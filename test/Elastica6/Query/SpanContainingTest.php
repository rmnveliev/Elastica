<?php
namespace Elastica6\Test\Query;

use Elastica6\Document;
use Elastica6\Query\SpanContaining;
use Elastica6\Query\SpanNear;
use Elastica6\Query\SpanTerm;
use Elastica6\Test\Base as BaseTest;

class SpanContainingTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray()
    {
        $field = 'name';
        $spanTermQuery1 = new SpanTerm([$field => 'nicolas']);
        $spanTermQuery2 = new SpanTerm([$field => ['value' => 'alekitto', 'boost' => 1.5]]);
        $spanTermQuery3 = new SpanTerm([$field => 'foobar']);
        $spanNearQuery = new SpanNear([$spanTermQuery1, $spanTermQuery2], 5);

        $spanContainingQuery = new SpanContaining($spanTermQuery3, $spanNearQuery);

        $expected = [
            'span_containing' => [
                'big' => [
                    'span_near' => [
                        'clauses' => [
                            [
                                'span_term' => [
                                    'name' => 'nicolas',
                                ],
                            ],
                            [
                                'span_term' => [
                                    'name' => [
                                        'value' => 'alekitto',
                                        'boost' => 1.5,
                                    ],
                                ],
                            ],
                        ],
                        'slop' => 5,
                        'in_order' => false,
                    ],
                ],
                'little' => [
                    'span_term' => [
                        'name' => 'foobar',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $spanContainingQuery->toArray());
    }

    /**
     * @group functional
     */
    public function testSpanContaining()
    {
        $field = 'lorem';
        $value = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse odio lacus, aliquam nec nulla quis, aliquam eleifend eros.';

        $index = $this->_createIndex();
        $type = $index->getType('test');

        $docHitData = [$field => $value];
        $doc = new Document(1, $docHitData);
        $type->addDocument($doc);
        $index->refresh();

        $spanTermQuery1 = new SpanTerm([$field => 'adipiscing']);
        $spanTermQuery2 = new SpanTerm([$field => 'lorem']);
        $spanNearQuery = new SpanNear([$spanTermQuery1, $spanTermQuery2], 5);

        $spanContainingQuery = new SpanContaining(new SpanTerm([$field => 'amet']), $spanNearQuery);
        $resultSet = $type->search($spanContainingQuery);
        $this->assertEquals(1, $resultSet->count());

        $spanContainingQuery = new SpanContaining(new SpanTerm([$field => 'not-matching']), $spanNearQuery);
        $resultSet = $type->search($spanContainingQuery);
        $this->assertEquals(0, $resultSet->count());
    }
}
