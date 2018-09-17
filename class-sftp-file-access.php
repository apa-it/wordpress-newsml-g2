<?php
error_reporting( E_ALL );

require_once( 'interface-file-access.php' );

/**
 * Implements the interface for file access and enables file access via SFTP.
 * This class uses the PHP-Module SSH2 - for more see http://php.net/manual/de/book.ssh2.php
 */
class SFTP_File_Access implements Interface_File_Access {

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
     * The SSH connection
     *
     * @var resource $_connection
     */
    private $_connection;

	/**
	 * The SFTP connection object
	 *
	 * @var resource $_sftp
	 */
	private $_sftp;

	/**
	 * The SFTP file descriptor
	 *
	 * @var resource $_sftp_fd
	 */
	private $_sftp_fd;

	/**
     * The folder where the news files will be stored temporarily.
     *
     * @var string $_temp_folder
     */
    private $_temp_folder = '/temp/';


    /**
     * Takes the passed arguments and sets $url, $username and $password, needed for the connection.
     *
     * @author Reinhard Stockinger
     *
     * @param string $url The URL where to find all the XML files.
     * @param string $username The username used to connect, default is empty.
     * @param string $password The password used to connect, default is empty.
     */
    public function __construct( $url, $username = '', $password = '' ) {

        $temp_url = substr( $url, 7 ); // Remove the sftp:// so users can insert a normal link
        $temp_url = substr( $temp_url, 0, -1 ); // Remove the trailing slash
        $this->_url = $temp_url;
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * Establishes the connection to the SFTP server and sets the private variable $_connection; $_sftp and $_sftp_fd
     * This class uses the SSH2 Module. For more see http://php.net/manual/de/book.ssh2.php
     *
     * @author Reinhard Stockinger
     */
    public function establish_connection() {

    	$host = $this->_url;
    	$port = 22;
    	//seperate host and port if url contains a port!
    	if (strpos($this->_url,":")){
    		$parts = explode(":",$this->_url);
    		$host = $parts[0];
    		$port = $parts[1];
	    }

	    // Set the private variables and create ftp connection
	    $this->_connection = ssh2_connect($host,$port);
	    if (! $this->_connection){
		    die( 'Error while connecting to SFTP server.' );
	    }
	    if (! ssh2_auth_password($this->_connection, $this->_username, $this->_password)){
		    die( 'Error while authenticating with SFTP server.' );
	    }
	    $this->_sftp = ssh2_sftp($this->_connection);
	    if (! $this->_sftp){
		    die( 'Error while initializing SFTP subsystem.' );
	    }
	    $this->sftp_fd = intval($this->_sftp);
    }

    /**
     * Parses the directory for the XML files we need.<br>
     * Ignores any other files than .xml
     *
     * @author Reinhard Stockinger
     *
     * @return array An array containing the filenames of all files (matching our criteria) the function found.
     */
    public function file_list() {

	    $realpath = ssh2_sftp_realpath($this->_sftp,"./");

	    $handle = opendir("ssh2.sftp://".$this->_sftp_fd.$realpath);

	    $items = array();
	    while(false != ($entry = readdir($handle))){
		    if ($entry == "." || $entry == ".."){
		    	continue;
		    }
		    $tmpfile = explode(".",$entry);
		    if (strtolower($tmpfile[1]) == 'xml' && strtolower($tmpfile[0]) != 'rss') {
			    array_push($items,$entry);
		    }
	    }
	    closedir($handle);
        return array_reverse($items);
    }

    /**
     * Saves the files locally so they can be used afterwards.
     *
     * @author Reinhard Stockinger
     *
     * @param array $files An array containing the filenames of all files to save.
     */
    public function save_files( $files ) {

        if ( ! file_exists( dirname( __FILE__ ) . $this->_temp_folder ) ) {
            mkdir( dirname( __FILE__ ) . $this->_temp_folder, 0777 );
        }

	    $realpath = ssh2_sftp_realpath($this->_sftp,"./");

	    foreach ( $files as $item ) {
		    $remote = @fopen("ssh2.sftp://".$this->_sftp_fd.$realpath."/".$item,"r");
		    $local = @fopen(dirname( __FILE__ ) . $this->_temp_folder . $item,"w");
		    $read = 0;
		    $filesize = filesize("ssh2.sftp://".$this->_sftp_fd.$realpath."/".$item);

		    while ($read < $filesize && ($buffer = fread($remote, $filesize - $read)))
		    {
			    $read += strlen($buffer);
			    if (fwrite($local, $buffer) === FALSE)
			    {
				    die("Unable to write to local file");
				    break;
			    }
		    }
		    fclose($local);
		    fclose($remote);
	    }
    }

    /**
     * Opens the passed $file in located $_url
     *
     * @author Reinhard Stockinger
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
     * @author Reinhard Stockinger
     *
     * @param string $path The path where the files should be stored.
     * @param array $filenames An array containing all filenames to save.
     */
    public function save_media_files( $path, $filenames ) {
        foreach ( $filenames as $file ) {
	        if (substr($file['href'],0,4) === "http"){
		        $name = array_slice(explode('/', rtrim($file['href'])),-1)[0];
		        if (substr($name,-3) === "jff"){ //replace jff extensions!
		        	$fileinfo = pathinfo($name);
		        	$name = $fileinfo['filename'] . ".jpg";
		        }
		        $picdata = file_get_contents( $file['href'] );
		        file_put_contents( $path . '/' . $name, $picdata );
	        }
	        else {
		        $name = substr( $file['href'], 2 );
		        if ( ftp_get( $this->_connection, $path . '/' . $name, $name, FTP_BINARY ) ) {
		        }
	        }
        }
    }

    /**
     * Removes all files in the passed $folder and finally removes the folder.
     *
     * @author Reinhard Stockinger
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
     * @author Reinhard Stockinger
     *
     * @param string $text The text or variable we want to printed beautiful.
     */
    private function debug( $text ) {
        echo '<pre>';
        print_r( $text );
        echo '</pre>';
    }
}

