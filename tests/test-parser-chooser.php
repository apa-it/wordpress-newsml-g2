<?php

class Parser_Chooser_Test extends WP_UnitTestCase {

    private $_apa = '/assets/apa1.xml';
    private $_kap = '/assets/kap1.xml';
    private $_rtr = '/assets/reuters1.xml';

    public function test_choose_parser_APA_successful() {
        $chooser = new Parser_Chooser();

        $file = file_get_contents( dirname( __FILE__ ) . $this->_apa );

        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadXML( $file );

        $parser = $chooser->choose_parser( $xml );

        $this->assertEquals( 'NewsML_Parser', get_class( $parser ) );
    }

    public function test_choose_parser_KAP_successful() {
        $chooser = new Parser_Chooser();

        $file = file_get_contents( dirname( __FILE__ ) . $this->_kap );

        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadXML( $file );

        $parser = $chooser->choose_parser( $xml );

        $this->assertEquals( 'NewsML_KAP_Parser', get_class( $parser ) );
    }

    public function test_choose_parser_RTR_successful() {
        $chooser = new Parser_Chooser();

        $file = file_get_contents( dirname( __FILE__ ) . $this->_rtr );

        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->loadXML( $file );

        $parser = $chooser->choose_parser( $xml );

        $this->assertEquals( 'NewsML_Reuters_Parser', get_class( $parser ) );
    }
}