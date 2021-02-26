<?php

namespace NewsML_G2\Plugin\Parser\RSS;

/**
 * Provides the capability to parse the APA specific rss.xml for the filenames.
 */
class RSSParser
{
    /**
     * The FileAccess used to get the rss.xml file we need.
     *
     * @var \NewsML_G2\Plugin\FileAccess\FileAccessType $_file_access
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
     * @param $file_access \NewsML_G2\Plugin\FileAccess\FileAccessType The class object used to access the files.
     */
    public function __construct($file_access)
    {
        $this->_file_access = $file_access;
    }

    /**
     * Creates an array containing all filenames.
     *
     * @return array An array containing all filenames we want to parse.
     * @author Bernhard Punz
     *
     */
    public function file_list()
    {
        $files = array($this->_filename);

        $this->_file_access->establish_connection();

        // Save the rss.xml
        $this->_file_access->save_files($files);
        $file = $this->_file_access->open_file($this->_filename);

        $xml = new \DOMDocument();
        $xml->loadXML($file);

        $items = array();

        foreach ($xml->getElementsByTagName('item') as $item) {
            $filename = $item->getElementsByTagName('link')->item(0)->nodeValue;

            $items[] = $filename;
        }
        $this->_file_access->recursive_rmdir();

        return $items;
    }
}
