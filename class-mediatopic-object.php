<?php

/**
* This class stores all information needed to be an entire mediatopic object.
*/
class Mediatopic {

    /**
     * The QCode of the mediatopic.
     *
     * @var string $_qcode
     */
    private $_qcode = "";

    /**
     * The date of the last modification.
     *
     * @var string $_modified
     */
    private $_modified = "";

    /**
     * The type of the mediatopic element.
     *
     * @var string $_type
     */
    private $_type = "";

    /**
     * The acutal name of the mediatopic.
     *
     * @var string $_name
     */
    private $_name = "";

    /**
     * The broader mediatopics of the mediatopic object.
     *
     * @var array $_broaders
     */
    private $_broaders = array();

    /**
     * The fulltext definition of the mediatopic.
     *
     * @var string $_definition
     */
    private $_definition = "";



    /**
     * Sets the QCode of the mediatopic object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_qcode( $value ) {
        $this->_qcode = $value;
    }

    /**
     * Returns the QCode of the mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_qcode() {
        return $this->_qcode;
    }

    /**
     * Sets the modification date of the mediatopic object to $value.
     *
     * @author Bernhard Punz
     *
     * @param int $value
     */
    public function set_modified( $value ) {
        $this->_modified = $value;
    }

    /**
     * Returns the modification date of the mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @return int
     */
    public function get_modified() {
        return $this->_modified;
    }

    /**
     * Sets the type of the mediatopic object to $value.
     *
     * @author Bernhard Punz
     *
     * @param $value
     */
    public function set_type( $value ) {
        $this->_type = $value;
    }

    /**
     * Returns the type of the mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_type() {
        return $this->_type;
    }

    /**
     * Sets the name of the mediatopic object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_name( $value ) {
        $this->_name = $value;
    }

    /**
     * Returns the name of the mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_name() {
        return $this->_name;
    }

    /**
     * Sets the broader mediatopics of the mediatopic object to $value.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function set_broaders( $value ) {
        $this->_broaders = $value;
    }

    /**
     * Returns the broader mediatopics of the mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @return array
     */
    public function get_broaders() {
        return $this->_broaders;
    }

    /**
     * Adds $value as broader mediatopic to mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function add_broader( $value ) {
        array_push( $this->_broaders, $value );
    }

    /**
     * Sets the definition of the mediatopic object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_definition( $value ) {
        $this->_definition = $value;
    }

    /**
     * Returns the definition of the mediatopic object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_definition() {
        return $this->_definition;
    }
}


