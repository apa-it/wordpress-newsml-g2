<?php

/**
 * Declares all functions that MUST be implemented for a working file access.
 */
interface Interface_File_Access {

    /**
     * Establishes the connection.
     *
     * @author Bernhard Punz
     */
    public function establish_connection();

    /**
     * Return the files to open as an array containing the filenames.
     *
     * @author Bernhard Punz
     *
     * @return array The array containing all filenames.
     */
    public function file_list();

    /**
     * Saves the passed xml files on the local filesystem.
     *
     * @author Bernhard Punz
     *
     * @param array $files An array containing the filenames of the XML files to save locally.
     */
    public function save_files( $files );

    /**
     * Returns the content of the file passed in $file.
     *
     * @author Bernhard Punz
     *
     * @param string $file The file which content is needed.
     * @param boolean $file_only Indicates if only a filename or a full path is passed.
     *
     * @return string The content of the file.
     */
    public function open_file( $file, $file_only = true );

    /**
     * Saves all files listed in $filenames found in the $path folder.
     *
     * @author Bernhard Punz
     *
     * @param string $path The local path or folder where all the images have to be stored temporarily.
     *
     * @param array $filenames An array containing the filenames of the files to save.
     */
    public function save_media_files( $path, $filenames );

    /**
     * Removes all files in the passed $folder and finally removes the folder.
     *
     * @author Bernhard Punz
     *
     * @param $folder Indicates the folder to remove. If $folder is null, the default /temp/ folder of the plugin will be remove.
     */
    public function recursive_rmdir( $folder = null );
}

?>