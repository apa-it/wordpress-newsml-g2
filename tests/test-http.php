<?php

class HTTP_Access_Test extends WP_UnitTestCase {

    private $_access;

    public function setUp() {

        $url = dirname( __FILE__ ).'/assets/';

        $this->_access = $this->getMockBuilder( 'HTTP_File_Access' )
            ->setConstructorArgs( array( $url, '', '' ) )
            ->setMethods( array( 'establish_connection' ) )
            ->getMock();
//        $this->_access->expects( $this->once() )->method( 'establish_connection' )->will( $this->returnValue( 'test' ) );
        $this->_access->expects( $this->once() )->method( 'establish_connection' )->will( $this->returnSelf() );
//        $this->_access->expects( $this->once() )->method( 'open_file' )->will( $this->returnValue( file_get_contents( dirname( __FILE__ ) . '/assets/apa1.xml' ) ) );
//        $this->_access->expects( $this->once() )->method( 'file_list' )->will( $this->returnValue( array() ) );
    }


    public function test_HTTP_file_list_successful() {

        $url = dirname( __FILE__ ) . '/assets/directorylisting.html';

        $this->_access->establish_connection();

        $list = $this->_access->file_list($url);

        $expected = array( 'apa1.xml', 'apa2.xml', 'apa3.xml', 'apa4.xml', 'apa5.xml', 'apa6.xml', );

        $this->assertEquals( $expected, $list );
    }

    public function test_HTTP_open_file_successful() {

        $this->_access->establish_connection();

        $filename = 'apa1.xml';
        unlink( 'temp/' . $filename );
        rmdir( 'temp/' );
        mkdir( 'temp/' );
        copy( dirname( __FILE__ ) . '/assets/' . $filename, 'temp/' . $filename );


        $file = $this->_access->open_file( $filename );

        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . '/assets/apa1.xml' ), $file );
    }

}