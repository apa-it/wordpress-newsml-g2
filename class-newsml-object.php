<?php

/**
 * This object stores a single news post
 */
class NewsML_Object {

    /**
     * The GUID of the news object.
     *
     * @var string $_guid
     */
    private $_guid = "";

    /**
     * The creation time of the news object.
     *
     * @var string $_timestamp
     */
    private $_timestamp = 0;

    /**
     * The filename of the news object.
     * @var string $_filename
     */
    private $_filename = "";

    /**
     * The version of the news object.
     * @var string $_version
     */
    private $_version = "";

    /**
     * The copyrightholder of the news object.
     *
     * @var string $_copyrightholder
     */
    private $_copyrightholder = "";

    /**
     * The copyrightnotice of the news object.
     *
     * @var string $_copyrightnotice
     */
    private $_copyrightnotice = "";

    /**
     * The title of the news object.
     *
     * @var string $_title
     */
    private $_title = "";

    /**
     * The subtitle of the news object.
     *
     * @var string $_subtitle
     */
    private $_subtitle = "";

    /**
     * The news objects of the news object.
     *
     * @var array $_mediatopics
     */
    private $_mediatopics = array();

    /**
     * The locations of the news object.
     *
     * @var array $_locations
     */
    private $_locations = array();

    /**
     * The content of the news object.
     *
     * @var string $_content
     */
    private $_content = "";

    /**
     * The multimedia files of the news object.
     *
     * @var array $_multimedia
     */
    private $_multimedia = array();

	/**
	 * The urgency of the news object
	 *
	 * @var string $_urgency
	 */
	private $_urgency = "";

	/**
	 * The desks of the news object.
	 *
	 * @var array $_multimedia
	 */
	private $_desks = array();

	/**
	 * The source of the news object
	 *
	 * @var string $_urgency
	 */
	private $_source = "";

    /**
     * Sets the QCode of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_guid( $value ) {
        $this->_guid = $value;
    }

    /**
     * Returns the QCode of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_guid() {
        return $this->_guid;
    }

    /**
     * Sets the creation date as unix timestamp of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param int $value
     */
    public function set_timestamp( $value ) {
        $this->_timestamp = $value;
    }

    /**
     * Returns the creation date of the news object as unix timestamp.
     *
     * @author Bernhard Punz
     *
     * @return int
     */
    public function get_timestamp() {
        return $this->_timestamp;
    }

    /**
     * Sets the filename of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_filename( $value ) {
        $this->_filename = $value;
    }

    /**
     * Returns the filename of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_filename() {
        return $this->_filename;
    }

    /**
     * Sets the version of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_version( $value ) {
        $this->_version = $value;
    }

    /**
     * Returns the version of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_version() {
        return $this->_version;
    }

    /**
     * Sets the copyrightholder of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_copyrightholder( $value ) {
        $this->_copyrightholder = $value;
    }

    /**
     * Returns the copyrightholder of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_copyrightholder() {
        return $this->_copyrightholder;
    }

    /**
     * Sets the copyrightnotice of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_copyrightnotice( $value ) {
        $this->_copyrightnotice = $value;
    }

    /**
     * Returns the copyrightnotice of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_copyrightnotice() {
        return $this->_copyrightnotice;
    }

    /**
     * Sets the title of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_title( $value ) {
        $this->_title = $value;
    }

    /**
     * Returns the title of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_title() {
        return $this->_title;
    }

    /**
     * Sets the subtitle of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_subtitle( $value ) {
        $this->_subtitle = $value;
    }

    /**
     * Returns the subtitle of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_subtitle() {
        return $this->_subtitle;
    }

    /**
     * Sets the mediatopics of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function set_mediatopics( $value ) {
        $this->_mediatopics = $value;
    }

    /**
     * Returns the mediatopics of the news object.
     *
     * @author Bernhard Punz
     *
     * @return array
     */
    public function get_mediatopics() {
        return $this->_mediatopics;
    }

    /**
     * Adds $value as mediatopic to the news object.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function add_mediatopics( $value ) {
        array_push( $this->_mediatopics, $value );
    }

    /**
     * Sets the locations of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function set_locations( $value ) {
        $this->_locations = $value;
    }

    /**
     * Returns the locations of the news object.
     *
     * @author Bernhard Punz
     *
     * @return array
     */
    public function get_locations() {
        return $this->_locations;
    }

    /**
     * Adds $value as location to the news object.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function add_location( $value ) {
        array_push( $this->_locations, $value );
    }

    /**
     * Sets the content of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param string $value
     */
    public function set_content( $value ) {
        $this->_content = $value;
    }

    /**
     * Returns the content of the news object.
     *
     * @author Bernhard Punz
     *
     * @return string
     */
    public function get_content() {
        return $this->_content;
    }

    /**
     * Sets the attached multimedia of the news object to $value.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function set_multimedia( $value ) {
        $this->_multimedia = $value;
    }

    /**
     * Returns the attached multimedia of the news object.
     *
     * @author Bernhard Punz
     *
     * @return array
     */
    public function get_multimedia() {
        return $this->_multimedia;
    }

    /**
     * Adds $value as multimedia to the news object.
     *
     * @author Bernhard Punz
     *
     * @param array $value
     */
    public function add_multimedia( $value ) {
        array_push( $this->_multimedia, $value );
    }

	/**
	 * Sets the urgency of the news object to $value.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param string $value
	 */
	public function set_urgency( $value ) {
		$this->_urgency = $value;
	}

	/**
	 * Returns the urgency of the news object.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @return string
	 */
	public function get_urgency() {
		return $this->_urgency;
	}

	/**
	 * Sets the desks of the news object to $value.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param array $value
	 */
	public function set_desks( $value ) {
		$this->_desks = $value;
	}

	/**
	 * Returns the desks of the news object.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @return array
	 */
	public function get_desks() {
		return $this->_desks;
	}

	/**
	 * Adds $value as desk to the news object.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param array $value
	 */
	public function add_desk( $value ) {
		array_push( $this->_desks, $value );
	}

	/**
	 * Sets the source of the news object to $value.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param string $value
	 */
	public function set_source( $value ) {
		$this->_source = $value;
	}

	/**
	 * Returns the source of the news object.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @return string
	 */
	public function get_source() {
		return $this->_source;
	}

}

