<?php
namespace Elastica6\Test\Query;

use Elastica6\Document;
use Elastica6\Query\AbstractQuery;
use Elastica6\Query\HasChild;
use Elastica6\Query\InnerHits;
use Elastica6\Query\MatchAll;
use Elastica6\Query\Nested;
use Elastica6\Query\SimpleQueryString;
use Elastica6\Script\Script;
use Elastica6\Script\ScriptFields;
use Elastica6\Test\Base as BaseTest;
use Elastica6\Type\Mapping;

class InnerHitsTest extends BaseTest
{
    private function _getIndexForNestedTest()
    {
        $index = $this->_createIndex();
        $type = $index->getType('questions');

        $mapping = new Mapping();
        $mapping->setType($type);

        // Set mapping
        $mapping->setProperties([
            'users' => [
                'type' => 'nested',
                'properties' => [
                    'name' => ['type' => 'text', 'fielddata' => true],
                ],
            ],
            'title' => ['type' => 'text'],
            'last_activity_date' => ['type' => 'date'],
        ]);

        // Send mapping to type
        $mapping->send();

        $type->addDocuments([
            new Document(1, [
                'users' => [
                    ['name' => 'John Smith', 'last_activity_date' => '2015-01-05'],
                    ['name' => 'Conan', 'last_activity_date' => '2015-01-05'],
                ],
                'last_activity_date' => '2015-01-05',
                'title' => 'Question about linux #1',
            ]),
            new Document(2, [
                'users' => [
                    ['name' => 'John Doe', 'last_activity_date' => '2015-01-05'],
                    ['name' => 'Simon', 'last_activity_date' => '2015-01-05'],
                ],
                'last_activity_date' => '2014-12-23',
                'title' => 'Question about linux #2',
            ]),
            new Document(3, [
                'users' => [
                    ['name' => 'Simon', 'last_activity_date' => '2015-01-05'],
                    ['name' => 'Garfunkel', 'last_activity_date' => '2015-01-05'],
                ],
                'last_activity_date' => '2015-01-05',
                'title' => 'Question about windows #1',
            ]),
            new Document(4, [
                'users' => [
                    ['name' => 'Einstein'],
                    ['name' => 'Newton'],
                    ['name' => 'Maxwell'],
                ],
                'last_activity_date' => '2014-12-23',
                'title' => 'Question about windows #2',
            ]),
            new Document(5, [
                'users' => [
                    ['name' => 'Faraday'],
                    ['name' => 'Leibniz'],
                    ['name' => 'Descartes'],
                ],
                'last_activity_date' => '2014-12-23',
                'title' => 'Question about osx',
            ]),
        ]);

        $index->refresh();

        return $index;
    }

    private function _getIndexForParentChildrenTest()
    {
        $index = $this->_createIndex();
        $questionType = $index->getType('questions');

        // Parent
        $mappingQuestion = new Mapping();
        $mappingQuestion->setType($questionType);

        // Set mapping
        $mappingQuestion->setProperties([
            'title' => ['type' => 'text'],
            'answer' => ['type' => 'text', 'fielddata' => true],
            'last_activity_date' => ['type' => 'date'],
            'my_join_field' => [
                'type' => 'join',
                'relations' => [
                    'questions' => 'answers',
                ],
            ],
        ]);

        $mappingQuestion->send();

        $questionType->addDocuments([
            new Document(1, [
                'last_activity_date' => '2015-01-05',
                'title' => 'Question about linux #1',
                'my_join_field' => [
                    'name' => 'questions',
                ],
            ], 'questions'),
            new Document(2, [
                'last_activity_date' => '2014-12-23',
                'title' => 'Question about linux #2',
                'my_join_field' => [
                    'name' => 'questions',
                ],
            ], 'questions'),
            new Document(3, [
                'last_activity_date' => '2015-01-05',
                'title' => 'Question about windows #1',
                'my_join_field' => [
                    'name' => 'questions',
                ],
            ], 'questions'),
            new Document(4, [
                'last_activity_date' => '2014-12-23',
                'title' => 'Question about windows #2',
                'my_join_field' => [
                    'name' => 'questions',
                ],
            ], 'questions'),
            new Document(5, [
                'last_activity_date' => '2014-12-23',
                'title' => 'Question about osx',
                'my_join_field' => [
                    'name' => 'questions',
                ],
            ], 'questions'),
        ]);

        $documentResponse1 = new Document(6, [
            'answer' => 'linux is cool',
            'last_activity_date' => '2016-01-05',
            'my_join_field' => [
                'name' => 'answers',
                'parent' => 1,
            ],
        ], 'questions', $index->getName());

        $documentResponse2 = new Document(7, [
            'answer' => 'linux is bad',
            'last_activity_date' => '2005-01-05',
            'my_join_field' => [
                'name' => 'answers',
                'parent' => 1,
            ],
        ], 'questions', $index->getName());

        $documentResponse3 = new Document(8, [
            'answer' => 'windows was cool',
            'last_activity_date' => '2005-01-05',
            'my_join_field' => [
                'name' => 'answers',
                'parent' => 2,
            ],
        ], 'questions', $index->getName());

        $this->_getClient()->addDocuments([$documentResponse1, $documentResponse2, $documentResponse3], ['routing' => 1]);

        $index->refresh();

        return $index;
    }

    /**
     * @group unit
     */
    public function testSetSize()
    {
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setSize(12);
        $this->assertEquals(12, $innerHits->getParam('size'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetFrom()
    {
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setFrom(12);
        $this->assertEquals(12, $innerHits->getParam('from'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetSort()
    {
        $sort = ['last_activity_date' => ['order' => 'desc']];
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setSort($sort);
        $this->assertEquals($sort, $innerHits->getParam('sort'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetSource()
    {
        $fields = ['title', 'tags'];
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setSource($fields);
        $this->assertEquals($fields, $innerHits->getParam('_source'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetVersion()
    {
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setVersion(true);
        $this->assertTrue($innerHits->getParam('version'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);

        $innerHits->setVersion(false);
        $this->assertFalse($innerHits->getParam('version'));
    }

    /**
     * @group unit
     */
    public function testSetExplain()
    {
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setExplain(true);
        $this->assertTrue($innerHits->getParam('explain'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);

        $innerHits->setExplain(false);
        $this->assertFalse($innerHits->getParam('explain'));
    }

    /**
     * @group unit
     */
    public function testSetHighlight()
    {
        $highlight = [
            'fields' => [
                'title',
            ],
        ];
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setHighlight($highlight);
        $this->assertEquals($highlight, $innerHits->getParam('highlight'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetFieldDataFields()
    {
        $fields = ['title', 'tags'];
        $innerHits = new InnerHits();
        $returnValue = $innerHits->setFieldDataFields($fields);
        $this->assertEquals($fields, $innerHits->getParam('docvalue_fields'));
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetScriptFields()
    {
        $script = new Script('1 + 2');
        $scriptFields = new ScriptFields(['three' => $script]);

        $innerHits = new InnerHits();
        $returnValue = $innerHits->setScriptFields($scriptFields);
        $this->assertEquals($scriptFields->toArray(), $innerHits->getParam('script_fields')->toArray());
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    /**
     * @group unit
     */
    public function testAddScriptField()
    {
        $script = new Script('2+3');
        $innerHits = new InnerHits();
        $returnValue = $innerHits->addScriptField('five', $script);
        $this->assertEquals(['five' => $script->toArray()], $innerHits->getParam('script_fields')->toArray());
        $this->assertInstanceOf(InnerHits::class, $returnValue);
    }

    private function getNestedQuery(AbstractQuery $query, InnerHits $innerHits)
    {
        $nested = (new Nested())
            ->setInnerHits($innerHits)
            ->setPath('users')
            ->setQuery($query);

        return $this->_getIndexForNestedTest()->search($nested);
    }

    private function getParentChildQuery(AbstractQuery $query, InnerHits $innerHits)
    {
        $child = (new HasChild($query, 'answers'))->setInnerHits($innerHits);

        return $this->_getIndexForParentChildrenTest()->getType('questions')->search($child);
    }

    /**
     * @group functional
     */
    public function testInnerHitsNested()
    {
        $queryString = new SimpleQueryString('windows newton', ['title', 'users.name']);
        $innerHits = new InnerHits();

        $results = $this->getNestedQuery($queryString, $innerHits);
        $firstResult = current($results->getResults());

        $innerHitsResults = $firstResult->getInnerHits();

        $this->assertEquals($firstResult->getId(), 4);
        $this->assertEquals($innerHitsResults['users']['hits']['hits'][0]['_source']['name'], 'Newton');
    }

    /**
     * @group functional
     */
    public function testInnerHitsParentChildren()
    {
        $queryString = new SimpleQueryString('linux cool');
        $innerHits = new InnerHits();

        $results = $this->getParentChildQuery($queryString, $innerHits);
        $firstResult = current($results->getResults());

        $innerHits = $firstResult->getInnerHits();

        $responses = $innerHits['answers']['hits']['hits'];
        $responsesId = [];

        foreach ($responses as $response) {
            $responsesId[] = $response['_id'];
        }

        $this->assertEquals($firstResult->getId(), 1);
        $this->assertEquals([6, 7], $responsesId);
    }

    /**
     * @group functional
     */
    public function testInnerHitsLimitedSource()
    {
        $this->markTestSkipped('Source filtering on inner hits is bugged. See https://github.com/elastic/elasticsearch/issues/21312');

        $innerHits = new InnerHits();
        $innerHits->setSource(['includes' => ['name'], 'excludes' => ['last_activity_date']]);

        $results = $this->getNestedQuery(new MatchAll(), $innerHits);

        foreach ($results as $row) {
            $innerHitsResult = $row->getInnerHits();
            foreach ($innerHitsResult['users']['hits']['hits'] as $doc) {
                $this->assertArrayHasKey('name', $doc['_source']['users']);
                $this->assertArrayNotHasKey('last_activity_date', $doc['_source']['users']);
            }
        }
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithOffset()
    {
        $queryString = new SimpleQueryString('linux cool');
        $innerHits = new InnerHits();
        $innerHits->setFrom(1);

        $results = $this->getParentChildQuery($queryString, $innerHits);
        $firstResult = current($results->getResults());

        $innerHits = $firstResult->getInnerHits();

        $responses = $innerHits['answers']['hits']['hits'];

        $this->assertEquals(count($responses), 1);
        $this->assertEquals(7, $responses[0]['_id']);
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithSort()
    {
        $queryString = new SimpleQueryString('linux cool');
        $innerHits = new InnerHits();
        $innerHits->setSort(['answer' => 'asc']);

        $results = $this->getParentChildQuery($queryString, $innerHits);
        $firstResult = current($results->getResults());

        $innerHits = $firstResult->getInnerHits();

        $responses = $innerHits['answers']['hits']['hits'];
        $responsesId = [];

        foreach ($responses as $response) {
            $responsesId[] = $response['_id'];
        }

        $this->assertEquals($firstResult->getId(), 1);
        $this->assertEquals([7, 6], $responsesId);
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithExplain()
    {
        $matchAll = new MatchAll();
        $innerHits = new InnerHits();
        $innerHits->setExplain(true);

        $results = $this->getNestedQuery($matchAll, $innerHits);

        foreach ($results as $row) {
            $innerHitsResult = $row->getInnerHits();
            foreach ($innerHitsResult['users']['hits']['hits'] as $doc) {
                $this->assertArrayHasKey('_explanation', $doc);
            }
        }
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithVersion()
    {
        $matchAll = new MatchAll();
        $innerHits = new InnerHits();
        $innerHits->setVersion(true);

        $results = $this->getNestedQuery($matchAll, $innerHits);

        foreach ($results as $row) {
            $innerHitsResult = $row->getInnerHits();
            foreach ($innerHitsResult['users']['hits']['hits'] as $doc) {
                $this->assertArrayHasKey('_version', $doc);
            }
        }
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithScriptFields()
    {
        $matchAll = new MatchAll();
        $innerHits = new InnerHits();
        $innerHits->setSize(1);
        $scriptFields = new ScriptFields();
        $scriptFields->addScript('three', new Script('1 + 2'));
        $scriptFields->addScript('five', new Script('3 + 2'));
        $innerHits->setScriptFields($scriptFields);

        $results = $this->getNestedQuery($matchAll, $innerHits);

        foreach ($results as $row) {
            $innerHitsResult = $row->getInnerHits();
            foreach ($innerHitsResult['users']['hits']['hits'] as $doc) {
                $this->assertEquals(3, $doc['fields']['three'][0]);
                $this->assertEquals(5, $doc['fields']['five'][0]);
            }
        }
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithHighlight()
    {
        $queryString = new SimpleQueryString('question simon', ['title', 'users.name']);
        $innerHits = new InnerHits();
        $innerHits->setHighlight(['fields' => ['users.name' => new \stdClass()]]);

        $results = $this->getNestedQuery($queryString, $innerHits);

        foreach ($results as $row) {
            $innerHitsResult = $row->getInnerHits();
            foreach ($innerHitsResult['users']['hits']['hits'] as $doc) {
                $this->assertArrayHasKey('highlight', $doc);
                $this->assertRegExp('#<em>Simon</em>#', $doc['highlight']['users.name'][0]);
            }
        }
    }

    /**
     * @group functional
     */
    public function testInnerHitsWithFieldData()
    {
        $queryString = new SimpleQueryString('question simon', ['title', 'users.name']);
        $innerHits = new InnerHits();

        $innerHits->setFieldDataFields(['users.name']);

        $results = $this->getNestedQuery($queryString, $innerHits);

        foreach ($results as $row) {
            $innerHitsResult = $row->getInnerHits();
            foreach ($innerHitsResult['users']['hits']['hits'] as $doc) {
                $this->assertArrayHasKey('fields', $doc);
                $this->assertArrayHasKey('users.name', $doc['fields']);
                $this->assertArrayNotHasKey('users.last_activity_date', $doc['fields']);
            }
        }
    }
}
