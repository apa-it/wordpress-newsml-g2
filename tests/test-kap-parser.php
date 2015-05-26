<?php

class Parser_KAP_Test extends WP_UnitTestCase {

    private $_file;
    private $_parser;
    private $_xml;

    public function setUp() {
        $this->_file = file_get_contents( dirname( __FILE__ ) . '/assets/kap1.xml' );
        $this->_parser = new NewsML_KAP_Parser();

        $this->_xml = new DOMDocument();
        $this->_xml->preserveWhiteSpace = false;
        $this->_xml->loadXML( $this->_file );
    }

    public function test_KAP_Parser_can_parse_successful() {

        $res = $this->_parser->can_parse( $this->_xml );

        $this->assertTrue( $res );
    }

    public function test_KAP_Parser_Object_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertNotEmpty( $res );
    }

    public function test_KAP_Parser_Title_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'Vatikan: Zahl der Priesteramtskandidaten weltweit rückläufig', $res->get_title() );
    }

    public function test_KAP_Parser_Subtitle_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'Stark rückläufig ist Zahl laut Statistik für 2013 in Tschechien (13 Prozent), Großbritannien (11,5 Prozent), Österreich (10,9 Prozent) und Polen (10 Prozent) - Zunahme weiterhin in Afrika', $res->get_subtitle() );
    }
}