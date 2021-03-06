<?php
namespace Elastica6\Test\Query;

use Elastica6\Document;
use Elastica6\Query\Fuzzy;
use Elastica6\Query\Prefix;
use Elastica6\Query\Regexp;
use Elastica6\Query\SpanMulti;
use Elastica6\Query\Wildcard;
use Elastica6\Test\Base as BaseTest;

class SpanMultiTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstructValid()
    {
        $field = 'name';
        $value = 'marek';

        $spanMultiQuery = new SpanMulti(new Regexp($field, $value, 0.7));
        $expected = [
            'span_multi' => [
                'match' => [
                    'regexp' => [
                        $field => [
                            'value' => $value,
                            'boost' => 0.7,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $spanMultiQuery->toArray());

        $spanMultiQuery = new SpanMulti();
        $spanMultiQuery->setMatch(new Fuzzy($field, $value));
        $expected = [
            'span_multi' => [
                'match' => [
                    'fuzzy' => [
                        $field => [
                            'value' => $value,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $spanMultiQuery->toArray());
    }

    /**
     * @group functional
     */
    public function testSpanMulti()
    {
        $field = 'lorem';
        $text1 = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
        $text2 = 'Praesent gravida nisi in lorem consectetur, vel ullamcorper leo iaculis.';
        $text3 = 'Vivamus vitae mi nec tortor iaculis pellentesque at nec ipsum.';
        $text4 = 'Donec tempor feugiat sapien, ac porta metus hendrerit nec';
        $text5 = 'Nullam pharetra mi vitae sollicitudin fermentum. Proin sed enim consequat, consectetur eros vitae, egestas metus';

        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->addDocuments([
            new Document(1, [$field => $text1]),
            new Document(2, [$field => $text2]),
            new Document(3, [$field => $text3]),
            new Document(4, [$field => $text4]),
            new Document(5, [$field => $text5]),
        ]);
        $index->refresh();

        $spanMultiQuery = new SpanMulti();

        //multi with prefix will match 3
        $prefixQuery = new Prefix([$field => ['value' => 'conse']]);
        $spanMultiQuery->setMatch($prefixQuery);
        $resultSet = $type->search($spanMultiQuery);
        $this->assertEquals(3, $resultSet->count());

        //multi with wildcard will match 3
        $wildcardQuery = new Wildcard($field, '*ll*');
        $spanMultiQuery->setMatch($wildcardQuery);
        $resultSet = $type->search($spanMultiQuery);
        $this->assertEquals(3, $resultSet->count());
    }
}
