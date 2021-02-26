<?php

namespace NewsML_G2\Plugin\FileAccess;

/**
 * Declares all functions that MUST be implemented for a working file access.
 */
interface FileAccessType
{
    /**
     * Establishes the connection.
     *
     * @author Bernhard Punz
     */
    public function establish_connection();

    /**
     * Return the files to open as an array containing the filenames.
     *
     * @return array The array containing all filenames.
     * @author Bernhard Punz
     *
     */
    public function file_list();

    /**
     * Saves the passed xml files on the local filesystem.
     *
     * @param array $files An array containing the filenames of the XML files to save locally.
     * @author Bernhard Punz
     *
     */
    public function save_files($files);

    /**
     * Returns the content of the file passed in $file.
     *
     * @param string $file The file which content is needed.
     * @param boolean $file_only Indicates if only a filename or a full path is passed.
     *
     * @return string The content of the file.
     * @author Bernhard Punz
     *
     */
    public function open_file($file, $file_only = true);

    /**
     * Saves all files listed in $filenames found in the $path folder.
     *
     * @param string $path The local path or folder where all the images have to be stored temporarily.
     *
     * @param array $filenames An array containing the filenames of the files to save.
     * @author Bernhard Punz
     *
     */
    public function save_media_files($path, $filenames);

    /**
     * Removes all files in the passed $folder and finally removes the folder.
     *
     * @param $folder
     *  Indicates the folder to remove. If $folder is null, the default /temp/ folder of the plugin will be remove.
     * @author Bernhard Punz
     *
     */
    public function recursive_rmdir($folder = null);
}
