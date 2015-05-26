<?php

require_once('interface-file-access.php');

/**
 * Implements the interface for file access and enables file access via HTTP.
 */
class HTTP_File_Access implements Interface_File_Access {

    /**
     * The URL used to connect to the server and get the news files.
     * @var string $_url
     */
    private $_url = '';

    /**
     * Takes the passed arguments and sets $url, $username and $password, needed for the connection.
     * In HTTP $username and $password is empty because it is not needed.
     *
     * @author Bernhard Punz
     *
     * @param string $url The URL where to find all the XML files.
     * @param string $username The username used to connect, default is empty.
     * @param string $password The password used to connect, default is empty.
     */
    public function __construct( $url, $username = '', $password = '' ) {

        $this->_url = $url;
    }

    /**
     * Establishes the connection to the server, using HTTP it is not necessary to connect.
     *
     * @author Bernhard Punz
     */
    public function establish_connection() {

        //do nothing, lol
    }

    /**
     * Parses the directory listing for the links to the XML files we need and want.<br>
     * Ignores any other files than .xml
     *
     * @author Bernhard Punz
     *
     * @param string $url The URL where the XML files are. Only used in the unittests.
     * @return array An array containing the filenames of all files (matching our criteria) the function found.
     */
    public function file_list( $url = null ) {

        if ( $url == null ) {
            $temp_list = file_get_contents( $this->_url );
        } else {
            $temp_list = file_get_contents( $url );
        }

        // First we get all links of the directory listing and then we turn the list around
        $count = preg_match_all( '/<a href="[^"]+">(?:(?!Name|Last modified|Size|Description).)[^<]*<\/a>/i', $temp_list, $files );

        $files = array_reverse( $files[0] );

        $read_entries = array();

        array_pop( $files ); // Remove the ../ link

        // Just loop through all files in the list
        for ( $k = 0; $k < count( $files ); $k++ ) {

            // Get the acutal link/filename as array (that's what preg_match_all does)
            $matche_res = preg_match_all( '/<a href="([^"]+)">[^<]*<\/a>/i', $files[$k], $temp_arr );

            // Our actual filename
            $file = $temp_arr[1][0];

            $parts = explode( '.', $file );
            if ( strtolower( $parts[1] ) == 'xml' && strtolower( $parts[0] ) != 'rss' ) { // Just add XML files to our array
                $read_entries[] = $temp_arr[1][0];
            }
        }

        return array_reverse( $read_entries );
    }

    /**
     * Does nothing, when using HTTP it's not necessary to save files locally.
     *
     * @author Bernhard Punz
     *
     * @param array $files An array containing the filenames of all files to save.
     */
    public function save_files( $files ) {
    }

    /**
     * Opens the passed $file in located $_url
     *
     * @author Bernhard Punz
     *
     * @param string $file The file which content is needed.
     * @param boolean $file_only Indicates if only a filename or a full path is passed.
     *
     * @return string The content of the file.
     */
    public function open_file( $file, $file_only = true ) {
        if ( $file_only ) {
            return file_get_contents( $this->_url . $file );
        } else {
            return file_get_contents( $file );
        }
    }

    /**
     * Saves the files passed as $filenames local in $path.
     *
     * @author Bernhard Punz
     *
     * @param string $path The path where the files should be stored.
     * @param array $filenames An array containing all filenames to save.
     */
    public function save_media_files( $path, $filenames ) {
        foreach ( $filenames as $file ) {
            $name = substr( $file['href'], 2 );
            file_put_contents( $path . '/' . $name, file_get_contents( $this->_url . $name ) );
        }
    }

    /**
     * Removes all files in the passed $folder and finally removes the folder.
     *
     * @author Bernhard Punz
     *
     * @param $folder
     */
    public function recursive_rmdir( $folder = null ) {

        $folder_to_delete = $folder;

        if ( is_dir( $folder_to_delete ) ) {
            $objects = scandir( $folder_to_delete );
            foreach ( $objects as $object ) {
                if ( $object != '.' && $object != '..' ) {
                    if ( filetype( $folder_to_delete . '/' . $object ) == 'dir' ) {
                        recursive_rmdir( $folder_to_delete . '/' . $object );
                    } else {
                        unlink( $folder_to_delete . '/' . $object );
                    }
                }
            }
            reset( $objects );
            rmdir( $folder_to_delete );
        }
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

