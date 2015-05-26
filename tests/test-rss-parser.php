<?php

//require_once('../../class-http-file-access.php');
require_once( dirname( dirname( __FILE__ ) ) . '/class-rss-parser.php' );

class Parser_RSS_Test extends WP_UnitTestCase {

    private $_access;

    public function setUp() {

        $path = dirname( __FILE__ ) . '/assets/';

        $this->_access = new HTTP_File_Access( $path, '', '' );

        $this->_access->establish_connection();
    }


    public function test_RSS_Parser_File_successfully_parsed() {

        $rss = new RSS_Parser( $this->_access );

        $list = $rss->file_list();

        $this->assertEquals( $list[0], '20150428-APA0473-1.xml' );
        $this->assertEquals( $list[42], '20150428-APA0438-1.xml' );
        $this->assertEquals( $list[99], '20150428-APA0394-1.xml' );
    }

}