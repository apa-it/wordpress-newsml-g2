<?php
error_reporting( E_ALL );

require_once( 'interface-file-access.php' );

/**
 * Implements the interface for file access and enables file access via FTP.
 */
class FTP_File_Access implements Interface_File_Access {

    /**
     *  The URL used to connect to the server and get the news files.
     *
     * @var string $_url
     */
    private $_url = '';

    /**
     * The username used to connect to the server.
     *
     * @var string $_username
     */
    private $_username = '';

    /**
     * The password used to connect to the server.
     *
     * @var string $_password
     */
    private $_password = '';

    /**
     * The FTP connection used to get all files.
     *
     * @var resource $_connection
     */
    private $_connection;

    /**
     * The folder where the news files will be stored temporarily.
     *
     * @var string $_temp_folder
     */
    private $_temp_folder = '/temp/';


    /**
     * Takes the passed arguments and sets $url, $username and $password, needed for the connection.
     *
     * @author Bernhard Punz
     *
     * @param string $url The URL where to find all the XML files.
     * @param string $username The username used to connect, default is empty.
     * @param string $password The password used to connect, default is empty.
     */
    public function __construct( $url, $username = '', $password = '' ) {

        $temp_url = substr( $url, 6 ); // Remove the ftp:// so users can insert a normal link
        $temp_url = substr( $temp_url, 0, -1 ); // Remove the trailing slash
        $this->_url = $temp_url;
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * Establishes the connection to the FTP server and sets the private variable $_connection
     *
     * @author Bernhard Punz
     */
    public function establish_connection() {

        // Set the private variables and create ftp connection
        $this->_connection = ftp_connect( $this->_url );

        $login_result = ftp_login( $this->_connection, $this->_username, $this->_password );

        ftp_pasv( $this->_connection, true );

        if ( ( ! $this->_connection ) || ( ! $login_result ) ) {
            die( 'Error while connecting to FTP server.' );
        } else {
            ftp_chdir( $this->_connection, 'apa_ots' ); // Just now needed because our files are in the folder apa_ots and you can not directly connect to a subfolder with FTP
        }
    }

    /**
     * Parses the directory for the XML files we need.<br>
     * Ignores any other files than .xml
     *
     * @author Bernhard Punz
     *
     * @return array An array containing the filenames of all files (matching our criteria) the function found.
     */
    public function file_list() {
        // Since we want all XML files we need to create a list
        $contents = ftp_rawlist( $this->_connection, '-t .' );

        $final_items = array();

        foreach ( $contents as $extracted ) {
            $splitted = explode( ' ', preg_replace( '/\s+/', ' ', $extracted ) ); // First remove multiple whitespaces, then split

            $tmp_file = explode( '.', $splitted[8] );
            if ( strtolower( $tmp_file[1] ) == 'xml' && strtolower( $tmp_file[0] ) != 'rss' ) {
                $final_items[] = $splitted[8];
            }
        }

        return array_reverse( $final_items );
    }

    /**
     * Saves the files locally so they can be used afterwards.
     *
     * @author Bernhard Punz
     *
     * @param array $files An array containing the filenames of all files ot save.
     */
    public function save_files( $files ) {

        if ( ! file_exists( dirname( __FILE__ ) . $this->_temp_folder ) ) {
            mkdir( dirname( __FILE__ ) . $this->_temp_folder, 0777 );
        }

        foreach ( $files as $item ) {
            // Save the files locally on our webspace
            if ( ftp_get( $this->_connection, dirname( __FILE__ ) . $this->_temp_folder . $item, $item, FTP_BINARY ) ) {
            }
        }
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
            return file_get_contents( dirname( __FILE__ ) . $this->_temp_folder . $file );
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
            if ( ftp_get( $this->_connection, $path . '/' . $name, $name, FTP_BINARY ) ) {
            }
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
        if ( $folder == null ) {
            $folder_to_delete = dirname( __FILE__ ) . $this->_temp_folder;
        } else {
            $folder_to_delete = $folder;
        }
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

