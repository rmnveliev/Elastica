<?php
namespace Elastica6\Test\Processor;

use Elastica6\Bulk;
use Elastica6\Document;
use Elastica6\Processor\Lowercase;
use Elastica6\ResultSet;
use Elastica6\Test\BasePipeline as BasePipelineTest;

class LowercaseTest extends BasePipelineTest
{
    /**
     * @group unit
     */
    public function testLowercase()
    {
        $processor = new Lowercase('foo');

        $expected = [
            'lowercase' => [
                'field' => 'foo',
            ],
        ];

        $this->assertEquals($expected, $processor->toArray());
    }

    /**
     * @group functional
     */
    public function testLowercaseField()
    {
        $lcase = new Lowercase('name');

        $pipeline = $this->_createPipeline('my_custom_pipeline', 'pipeline for Lowercase');
        $pipeline->addProcessor($lcase)->create();

        $index = $this->_createIndex();
        $type = $index->getType('bulk_test');

        // Add document to normal index
        $doc1 = new Document(null, ['name' => 'RUFLIN']);
        $doc2 = new Document(null, ['name' => 'NICOLAS']);

        $bulk = new Bulk($index->getClient());
        $bulk->setIndex($index);
        $bulk->setType($type);

        $bulk->addDocuments([
            $doc1, $doc2,
        ]);
        $bulk->setRequestParam('pipeline', 'my_custom_pipeline');

        $bulk->send();
        $index->refresh();

        /** @var ResultSet $result */
        $result = $index->search('*');

        $this->assertEquals(2, count($result->getResults()));

        $results = $result->getResults();
        $this->assertSame('ruflin', ($results[0]->getHit())['_source']['name']);
        $this->assertSame('nicolas', ($results[1]->getHit())['_source']['name']);
    }
}
