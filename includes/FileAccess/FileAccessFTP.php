<?php

namespace NewsML_G2\Plugin\FileAccess;

error_reporting(E_ALL);

use phpseclib3\Net\SFTP;
use NewsML_G2\Plugin\Tools\ToolsDir;

/**
 * Implements the interface for file access and enables file access via FTP.
 */
class FileAccessFTP implements FileAccessType
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
    public $_temp_folder = NEWSML_DIR . '/temp/';

    /**
     * FileAccess type.
     *
     * @var string $type
     */
    public $type = 'ftp';

    /**
     * Protocol secure connection.
     *
     * @var bool $is_secured
     * @since 1.2.0
     */
    public $is_secured = false;


    /**
     * Takes the passed arguments and sets $url, $username and $password, needed for the connection.
     *
     * @param string $url The URL where to find all the XML files.
     * @param string $username The username used to connect, default is empty.
     * @param string $password The password used to connect, default is empty.
     * @author Bernhard Punz
     *
     */
    public function __construct($url = '', $username = '', $password = '')
    {
        $url_data = parse_url($url);

        $this->_host = $url_data['host'] ?? '';
        $this->_path = $url_data['path'] ?? '';
        $this->_url = $this->_host . rtrim($this->_path, '/');
        $this->_scheme = $url_data['scheme'] ?? 'ftp';
        $this->_port = (int)($url_data['port'] ?? 21);
        $this->_username = $url_data['user'] ?? $username;
        $this->_password = $url_data['pass'] ?? $password;

        if ($this->_scheme !== 'ftp') {
            $this->is_secured = true;
        }
    }

    /**
     * Establishes the connection to the FTP server and sets the private variable $_connection.
     *
     * @author Bernhard Punz
     */
    public function establish_connection()
    {
        if (!$this->is_secured) {
            // Set the private variables and create ftp connection.
            $this->_connection = ftp_connect($this->_url);

            $login_result = ftp_login($this->_connection, $this->_username, $this->_password);

            ftp_pasv($this->_connection, true);

            if ((!$this->_connection) || (!$login_result)) {
                die('Error while connecting to FTP server.');
            }

            ftp_chdir(
                $this->_connection,
                'apa_ots'
            ); // Just now needed because our files are in the folder apa_ots and you can not directly connect to a subfolder with FTP.
        } else {
            $this->establish_connection_secured();
        }
    }

    /**
     * Establishes the connection to the SFTP server and sets the private variable $_connection.
     *
     * @author Alexander Kucherov
     * @since 1.2.0
     * @access protected
     */
    protected function establish_connection_secured()
    {
        $this->_connection = new SFTP($this->_host, $this->_port);
        $login_result = $this->_connection->login($this->_username, $this->_password);

        if (!$this->_connection || !$login_result) {
            die('Error while connecting to FTP server.');
        }

        $this->_connection->chdir($this->_path);
    }

    /**
     * Parses the directory for the XML files we need.<br>
     * Ignores any other files than .xml
     *
     * @return array An array containing the filenames of all files (matching our criteria) the function found.
     * @author Bernhard Punz
     *
     */
    public function file_list()
    {
        if ($this->is_secured) {
            return $this->file_list_secured();
        }

        // Since we want all XML files we need to create a list
        $contents = ftp_rawlist($this->_connection, '-t .');

        $final_items = array();
        foreach ($contents as $extracted) {
            $splitted = explode(
                ' ',
                preg_replace('/\s+/', ' ', $extracted)
            ); // First remove multiple whitespaces, then split

            $tmp_file = explode('.', $splitted[8]);
            if (strtolower($tmp_file[1]) === 'xml' && strtolower($tmp_file[0]) !== 'rss') {
                $final_items[] = $splitted[8];
            }
        }

        return array_reverse($final_items, true);
    }

    /**
     * Parses the directories for the XML files we need.<br>
     * Ignores any other files than .xml & .zip
     *
     * @param string $new_path
     *  Path to sub directory.
     * @return array
     *  An array containing the filenames of all files (matching our criteria) the function found.
     *
     * @author Alexander Kucherov
     * @since 1.2.0
     * @access protected
     */
    protected function file_list_secured($new_path = ''): array
    {
        if (!($this->_connection instanceof SFTP)) {
            return array();
        }

        if ($new_path) {
            $this->_connection->chdir($new_path);
        }

        $read_entries = array();
        $contents = $this->_connection->rawlist();

        foreach ($contents as $content => $description) {
            // Sub-folder.
            if (!in_array($content, array('.', '..')) && $description['type'] === 2) {
                $read_entries[$content] = $this->file_list_secured($content);
            }
        }

        foreach ($contents as $content => $description) {
            $parts = explode('.', $content);
            if (count($parts) > 1) {
                if (in_array(strtolower($parts[1]), array('zip', 'xml'))
                    && strtolower($parts[0]) !== 'rss') { // Just add XML & ZIP files to our array.
                    $read_entries[] = $content;
                }
            }
        }

        // Back to parent directory.
        if ($new_path) {
            $this->_connection->chdir('..');
        }

        return array_reverse($read_entries, true);
    }

    /**
     * Saves the files locally so they can be used afterwards.
     *
     * @param array $files An array containing the filenames of all files ot save.
     * @author Bernhard Punz
     *
     */
    public function save_files($files, $sub_path = '')
    {
        $path = $this->_temp_folder;
        if (!file_exists($path)) {
            if (!mkdir($path) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }

        // Save the files locally on our webspace
        foreach ($files as $key => $item) {
            if (is_array($item)) {
                $this->save_files($files[$key], $sub_path . $key . '/');
                continue;
            }
            $from = $sub_path . $item;
            $to = $path . $item;
            if ($this->is_secured && $this->_connection instanceof SFTP) {
                $this->_connection->get($from, $to);
            } else {
                ftp_get($this->_connection, $to, $from, FTP_BINARY);
            }

            // Unzip.
            if (explode('.', $item)[1] === 'zip') {
                $unzip = new \ZipArchive();
                $out = $unzip->open(realpath($path . $item));
                if ($out === true) {
                    $unzip->extractTo(realpath($path));
                    $unzip->close();
                } else {
                    error_log("[NewsML_G2] - $item  - unzipped with error: $out", 0);
                }
            }
        }
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
            return file_get_contents($this->_temp_folder . $file);
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
            ftp_get($this->_connection, $path . '/' . $name, $name, FTP_BINARY);
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
        $folder_to_delete = $folder ?? $this->_temp_folder;
        if (is_dir($folder_to_delete)) {
            $objects = scandir($folder_to_delete);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($folder_to_delete . '/' . $object) === 'dir') {
                        ToolsDir::rmdir_recursive($folder_to_delete . '/' . $object);
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
