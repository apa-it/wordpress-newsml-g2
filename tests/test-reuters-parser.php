<?php

class Parser_Reuters_Test extends WP_UnitTestCase {

    private $_file;
    private $_parser;
    private $_xml;

    public function setUp() {
        $this->_file = file_get_contents( dirname( __FILE__ ) . '/assets/reuters1.xml' );
        $this->_parser = new NewsML_Reuters_Parser();

        $this->_xml = new DOMDocument();
        $this->_xml->preserveWhiteSpace = false;
        $this->_xml->loadXML( $this->_file );
    }

    public function test_Reuters_Parser_can_parse_successful() {

        $res = $this->_parser->can_parse( $this->_xml );

        $this->assertTrue( $res );
    }

    public function test_Reuters_Parser_Object_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertNotEmpty( $res );
    }

    public function test_Reuters_Parser_Copyrightholder_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'Thomson Reuters', $res->get_copyrightholder() );
    }

    public function test_Reuters_Parser_Title_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'China complains Japanese air, sea surveillance raises safety risks', $res->get_title() );
    }

    public function test_Reuters_Parser_Mediatopics_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $medtops = $res->get_mediatopics();

        $this->assertEquals( 'subj:11001000', $medtops[0]['qcode'] );
        $this->assertEquals( 'subj:11002000', $medtops[1]['qcode'] );
        $this->assertEquals( 'subj:11001002', $medtops[2]['qcode'] );
        $this->assertEquals( 'subj:11000000', $medtops[3]['qcode'] );
        $this->assertEquals( 'subj:10000000', $medtops[4]['qcode'] );
        $this->assertEquals( 'subj:16009000', $medtops[5]['qcode'] );
    }
}