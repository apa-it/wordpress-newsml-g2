<?php

/**
 * Provides the capability to parse the APA specific rss.xml for the filenames.
 */
class RSS_Parser {

    /**
     * The FileAccess used to get the rss.xml file we need.
     *
     * @var Interface_File_Access $_file_access
     */
    private $_file_access;

    /**
     * The filename of the RSS feed where the filenames are.
     *
     * @var string $_filename
     */
    private $_filename = 'rss.xml';

    /**
     * Sets the variable $_file_access.
     * 
     * @param $file_access The class object used to access the files.
     */
    public function __construct( $file_access ) {
        $this->_file_access = $file_access;
    }

    /**
     * Creates an array containing all filenames.
     *
     * @author Bernhard Punz
     *
     * @return array An array containing all filenames we want to parse.
     */
    public function file_list() {

        $files = array( $this->_filename );

        $this->_file_access->establish_connection();

        // Save the rss.xml
        $this->_file_access->save_files( $files );
        $file = $this->_file_access->open_file( $this->_filename );

        $xml = new DOMDocument();
        $xml->loadXML( $file );

        $items = array();

        foreach ( $xml->getElementsByTagName( 'item' ) as $item ) {

            $filename = $item->getElementsByTagName( 'link' )->item( 0 )->nodeValue;

            $items[] = $filename;

        }
        $this->_file_access->recursive_rmdir();

        return $items;
    }

    /**
     * Just a private debug function to beautify the output when debugging.
     *
     * @author Bernhard Punz
     *
     * @param string $text The text or variable we want to printed beautiful.
     */
    private function debug( $text ) {
        echo '<pre>';
        print_r( $text );
        echo '</pre>';
    }


}