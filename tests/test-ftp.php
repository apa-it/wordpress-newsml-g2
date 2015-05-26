<?php

class FTP_Access_Test extends WP_UnitTestCase {

    private $_access;

    public function setUp() {
        $this->_access = $this->getMockBuilder( 'FTP_File_Access' )
            //->setConstructorArgs( array( 'ftp://crsle1.crs.apa.at/', 'crsplwordpressg2', 'smhKo7MRSLzF27' ) )
            ->setConstructorArgs( array( 'ftp://some.ftp.server.com/', 'username', 'password' ) )
            ->setMethods( array( 'establish_connection', 'file_list' ) )
            ->getMock();
        $this->_access->expects( $this->once() )->method( 'establish_connection' )->will( $this->returnSelf() );
    }


    public function test_FTP_open_file_successful() {
        $filename = 'apa1.xml';
        mkdir( dirname( __FILE__ ) . '/assets/temp/' );
        copy( dirname( __FILE__ ) . '/assets/' . $filename, dirname( __FILE__ ) . '/assets/temp/' . $filename );

        $this->_access->establish_connection();

        $file = $this->_access->open_file( dirname( __FILE__ ) . '/assets/temp/' . $filename, false );

        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . '/assets/apa1.xml' ), $file );
        unlink( dirname( __FILE__ ) . '/assets/temp/' . $filename );
        rmdir( dirname( __FILE__ ) . '/assets/temp/' );
    }

}