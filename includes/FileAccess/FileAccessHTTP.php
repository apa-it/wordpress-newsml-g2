<?php

namespace NewsML_G2\Plugin\FileAccess;

/**
 * Implements the interface for file access and enables file access via HTTP.
 */
class FileAccessHTTP implements FileAccessType
{
    /**
     * The URL used to connect to the server and get the news files.
     *
     * @var string $_url
     * @access private
     */
    private $_url = '';

    /**
     * The URL hostname.
     *
     * @var string
     * @access private
     * @since 1.2.0
     */
    private $_host = '';

    /**
     * The URL path.
     *
     * @var string
     * @access private
     * @since 1.2.0
     */
    private $_path = '';

    /**
     * The URL scheme.
     *
     * @var string
     * @access private
     * @since 1.2.0
     */
    private $_scheme = '';

    /**
     * The URL port.
     *
     * @var string
     * @access private
     * @since 1.2.0
     */
    private $_port = '';

    /**
     * The username used to connect to the server.
     *
     * @var string $_username
     * @access private
     */
    private $_username = '';

    /**
     * The password used to connect to the server.
     *
     * @var string $_password
     */
    private $_password = '';

    /**
     * FileAccess type.
     * @var string $type
     */
    public $type = 'http';

    /**
     * Takes the passed arguments and sets $url, $username and $password, needed for the connection.
     * In HTTP $username and $password is empty because it is not needed.
     *
     * @param string $url The URL where to find all the XML files.
     * @param string $username The username used to connect, default is empty.
     * @param string $password The password used to connect, default is empty.
     * @author Bernhard Punz
     *
     */
    public function __construct($url, $username = '', $password = '')
    {
        $this->_url = $url;

        $url_data = parse_url($url);
        $this->_host = $url_data['host'] ?? '';
        $this->_path = $url_data['path'] ?? '';
        $this->_scheme = $url_data['scheme'] ?? '';
        $this->_port = (int)($url_data['port'] ?? 80);
        $this->_username = $url_data['user'] ?? $username;
        $this->_password = $url_data['pass'] ?? $password;
    }

    /**
     * Establishes the connection to the server, using HTTP it is not necessary to connect.
     *
     * @author Bernhard Punz
     */
    public function establish_connection()
    {
        //do nothing, lol
    }

    /**
     * Parses the directory listing for the links to the XML files we need and want.<br>
     * Ignores any other files than .xml
     *
     * @param string $url The URL where the XML files are. Only used in the unittests.
     * @return array An array containing the filenames of all files (matching our criteria) the function found.
     * @author Bernhard Punz
     *
     */
    public function file_list($url = null)
    {
        if ($url == null) {
            $temp_list = file_get_contents($this->_url);
        } else {
            $temp_list = file_get_contents($url);
        }

        // First we get all links of the directory listing and then we turn the list around
        $count = preg_match_all(
            '/<a href="[^"]+">(?:(?!Name|Last modified|Size|Description).)[^<]*<\/a>/i',
            $temp_list,
            $files
        );

        $files = array_reverse($files[0]);
        $files_count = count($files);

        $read_entries = array();

        // Just loop through all files in the list
        for ($k = 0; $k < $files_count; $k++) {
            // Get the acutal link/filename as array (that's what preg_match_all does)
            $matche_res = preg_match_all('/<a href="([^"]+)">[^<]*<\/a>/i', $files[$k], $temp_arr);

            // Our actual filename
            $file = $temp_arr[1][0];

            // Sub-dir
            if (substr($file, -1) === '/') {
                $sub_dir = new \SimpleXMLElement($files[$k]);
                $read_entries[$this->_url . $sub_dir['href']] = $this->file_list($this->_url . $sub_dir['href']);
            }

            $parts = explode('.', $file);
            if (count($parts) > 1) {
                if (strtolower($parts[1]) === 'xml' && strtolower(
                        $parts[0]
                    ) !== 'rss') { // Just add XML files to our array
                    $read_entries[] = $temp_arr[1][0];
                }
            }
        }

        return array_reverse($read_entries, true);
    }

    /**
     * Does nothing, when using HTTP it's not necessary to save files locally.
     *
     * @param array $files An array containing the filenames of all files to save.
     * @author Bernhard Punz
     *
     */
    public function save_files($files)
    {
    }

    /**
     * Opens the passed $file in located $_url
     *
     * @param string $file The file which content is needed.
     * @param boolean $file_only Indicates if only a filename or a full path is passed.
     *
     * @return string The content of the file.
     * @author Bernhard Punz
     *
     */
    public function open_file($file, $file_only = true)
    {
        if ($file_only) {
            return file_get_contents($this->_url . $file);
        }

        return file_get_contents($file);
    }

    /**
     * Saves the files passed as $filenames local in $path.
     *
     * @param string $path The path where the files should be stored.
     * @param array $filenames An array containing all filenames to save.
     * @author Bernhard Punz
     *
     */
    public function save_media_files($path, $filenames)
    {
        foreach ($filenames as $file) {
            $name = substr($file['href'], 2);
            file_put_contents($path . '/' . $name, file_get_contents($this->_url . $name));
        }
    }

    /**
     * Removes all files in the passed $folder and finally removes the folder.
     *
     * @param $folder
     * @author Bernhard Punz
     *
     */
    public function recursive_rmdir($folder = null)
    {
        $folder_to_delete = $folder;

        if (is_dir($folder_to_delete)) {
            $objects = scandir($folder_to_delete);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($folder_to_delete . '/' . $object) === 'dir') {
                        $this->recursive_rmdir($folder_to_delete . '/' . $object);
                    } else {
                        unlink($folder_to_delete . '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($folder_to_delete);
        }
    }
}
