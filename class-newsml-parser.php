<?php

error_reporting( E_ALL );
set_time_limit( 360 );

require_once( 'class-newsml-object.php' );
require_once( 'interface-file-access.php' );

/**
 * Provides the capability to parse a XML file containing NewsML-G2 data from APA/OTS.
 */
class NewsML_Parser {

    /**
     * The unique identifier which is used by can_parse to determine if the file to parse is from APA/OTS.
     *
     * @var string $_provider
     */
    private $_provider = 'nprov:apa';

    /**
     * Checks if the DOM Object is from APA/OTS and parsable by this parser.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $file The DOM Tree of the file to parse.
     *
     * @return bool True if the file is parsable by this parser.
     */
    public function can_parse( $file ) {
        $xpath = $this->generate_xpath_on_xml( $file );
        $query = '//tempNS:newsMessage/tempNS:itemSet/tempNS:newsItem/tempNS:itemMeta/tempNS:provider';
        $result = $xpath->query( $query );

        if ( $result->item( 0 ) != null && $result->item( 0 )->getAttribute( 'qcode' ) == $this->_provider ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parses the DOM Object, fetches all required data and from it and returns them as NewsML_Object.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $file The DOM Tree of the file to parse.
     *
     * @return NewsML_Object The filled NewsML_Object.
     */
    public function parse( $file ) {

        // Create a new NewsML_Object that stores all our data and will be added later to an array
        /**
         * @var NewsML_Object $news_object
         */
        $news_object = new NewsML_Object();

        // Generate the XPath
        $xpath = $this->generate_xpath_on_xml( $file );
        $query = '//tempNS:newsMessage/tempNS:itemSet'; // all itemSet
        $result = $xpath->query( $query );

        $guid = $version = $copyrightholder = $copyrightnotice = $timestamp = $content = $urgency = $source = '';
        $titles = array_fill_keys( array( 'title', 'subtitle' ), '' );
        $mediatopics = $locations = $desks = array();

        // We loop through all itemSets, those can be a packageItem or newsItem
        foreach ( $result->item( 0 )->childNodes as $child ) { // packageItem, newsItem

            // Now we need to get the itemClass so we can differ between pictures and text
            $var = $child->getElementsByTagName( 'itemClass' )->item( 0 );

            // If it is a picture or a graphic
            if ( $var->getAttribute( 'qcode' ) == 'ninat:picture' || $var->getAttribute( 'qcode' ) == 'ninat:graphics' ) {

                // Of course we want all pictures, so we get them all
                $remote_contents = $child->getElementsByTagName( 'remoteContent' );

                $remote = array();

                // Loop through all the pictures and add the filenames to the array
                foreach ( $remote_contents as $media ) {

                    $topic = array(
                        'href' => $media->getAttribute( 'href' ),
                    );

                    $news_object->add_multimedia( $topic );

                    $remote[] = $topic;
                }

                //Test to import ABD
	            $textitem = $var->parentNode->parentNode;

	            $doc = new DOMDocument();
	            $doc->formatOutput = true;
	            $doc->loadXML( '<root></root>' );
	            $doc->preserveWhiteSpace = false;

	            $to_import = $doc->importNode( $textitem, true );
	            $doc->documentElement->appendChild( $to_import );

	            $guid = $this->get_guid_from_newsml( $doc );
	            $version = $this->get_version_from_newsml( $doc );
	            $copyrightholder = $this->get_copyrightholder_from_newsml( $doc );
	            $copyrightnotice = $this->get_copyrightnotice_from_newsml( $doc );
	            $timestamp = $this->get_datetime_from_newsml( $doc );
	            $titles = $this->get_titles_from_newsml( $doc );
	            $mediatopics = $this->get_mediatopics_from_newsml( $doc );
	            $content = $this->get_content_from_newsml( $doc );
	            $locations = $this->get_locations_from_newsml( $doc );
	            $urgency = $this->get_urgency_from_newsml( $doc );
	            $desks = $this->get_desks_from_newsml( $doc );
	            $source = $this->get_source_from_newsml( $doc );


                // So it is a text, so we do some stuff and add that stuff to our NewsML_Object
            } elseif ( $var->getAttribute( 'qcode' ) == 'ninat:text' ) {

                $textitem = $var->parentNode->parentNode;

                $doc = new DOMDocument();
                $doc->formatOutput = true;
                $doc->loadXML( '<root></root>' );
                $doc->preserveWhiteSpace = false;

                $to_import = $doc->importNode( $textitem, true );
                $doc->documentElement->appendChild( $to_import );

                $doc_to_work = $doc->saveXML();

                $guid = $this->get_guid_from_newsml( $doc );
                $version = $this->get_version_from_newsml( $doc );
                $copyrightholder = $this->get_copyrightholder_from_newsml( $doc );
                $copyrightnotice = $this->get_copyrightnotice_from_newsml( $doc );
                $timestamp = $this->get_datetime_from_newsml( $doc );
                $titles = $this->get_titles_from_newsml( $doc );
                $mediatopics = $this->get_mediatopics_from_newsml( $doc );
                $content = $this->get_content_from_newsml( $doc );
                $locations = $this->get_locations_from_newsml( $doc );
                $urgency = $this->get_urgency_from_newsml( $doc );
	            $desks = $this->get_desks_from_newsml( $doc );
	            $source = $this->get_source_from_newsml( $doc );
            }
        }

        $news_object->set_guid( $guid );
        $news_object->set_timestamp( $timestamp );
        $news_object->set_version( $version );
        $news_object->set_copyrightholder( $copyrightholder );
        $news_object->set_copyrightnotice( $copyrightnotice );
        $news_object->set_title( $titles['title'] );
        $news_object->set_subtitle( $titles['subtitle'] );
        $news_object->set_mediatopics( $mediatopics );
        $news_object->set_locations( $locations );
        $news_object->set_content( $content );
        $news_object->set_urgency($urgency);
        $news_object->set_desks($desks);
        $news_object->set_source($source);

        return $news_object;
    }

    /**
     * Gets the GUID of the news message and returns it.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The GUID if found, otherwise an empty string.
     */
    public function get_guid_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_guid = '//tempNS:newsItem';
        $result_guid = $xpath->query( $query_guid );

        $guid = $result_guid->item( 0 )->getAttribute( 'guid' );

        if ( $guid != '' ) {
            return $guid;
        } else {
            return '';
        }
    }

    /**
     * Gets the version of the news message and returns it.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The version of the news object.
     */
    public function get_version_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_version = '//tempNS:newsItem';
        $result_version = $xpath->query( $query_version );

        $version = $result_version->item( 0 )->getAttribute( 'version' );

        if ( $version != '' ) {
            return $version;
        } else {
            return '1';
        }
    }

    /**
     * Gets the copyrightholder information and returns it.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The copyrightholder if found, otherwise an empty string.
     */
    public function get_copyrightholder_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_holder = '//tempNS:rightsInfo/tempNS:copyrightHolder/tempNS:name';
        $result_holder = $xpath->query( $query_holder );

        $holder = $result_holder->item( 0 )->nodeValue;

        if ( $holder != '' ) {
            return $holder;
        } else {
            return '';
        }
    }

    /**
     * Gets the copyrightnotice information and returns it.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The copyrightnotice if found, otherwise an empty string.
     */
    public function get_copyrightnotice_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_notice = '//tempNS:rightsInfo/tempNS:copyrightNotice';
        $result_notice = $xpath->query( $query_notice );

        $notice = $result_notice->item( 0 )->nodeValue;

        if ( $notice != '' ) {
            return $notice;
        } else {
            return '';
        }
    }

    /**
     * Gets the title and subtitle of the news message and returns them as an array.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return array The titles if found, otherwise an array with empty values.
     */
    public function get_titles_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_title = '//tempNS:headline[@role="apahltype:title"]';
        $result_title = $xpath->query( $query_title );

        $title = "";
        if ( $result_title->length > 0 ) {
            $title = $result_title->item( 0 )->nodeValue;
        }

        $query_subtitle = '//tempNS:headline[@role="apahltype:subtitle"]';
        $result_subtitle = $xpath->query( $query_subtitle );

        $subtitle = "";
        if ( $result_subtitle->length > 0 ) {
            $subtitle = $result_subtitle->item( 0 )->nodeValue;
        }

        $all_titles = array(
            'title' => $title,
            'subtitle' => $subtitle,
        );

        return $all_titles;
    }

    /**
     * Gets the creation time from the XML and returns it as XML DateTime.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The creation time if found, otherwise an empty string.
     */
    public function get_datetime_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_datetime = '//tempNS:versionCreated';
        $result_datetime = $xpath->query( $query_datetime );

        // Convert from XML datetime to a unix timestamp
        $timestamp = strtotime( $result_datetime->item( 0 )->nodeValue );

        if ( $timestamp != '' ) {
            return $timestamp;
        } else {
            return '';
        }
    }

	/**
	 * Gets the urgency from the XML and returns it as XML String.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param DOMDocument $xml The DOM Tree of the file to parse.
	 *
	 * @return string The urgency if found, otherwise an empty string.
	 */
	public function get_urgency_from_newsml( $xml ) {
	    $xpath = $this->generate_xpath_on_xml( $xml );

	    $query_urgency = '//tempNS:urgency';
	    $result_urgency = $xpath->query( $query_urgency );

	    $urgency = $result_urgency->item( 0 )->nodeValue;

	    if ( $urgency != '' ) {
		    return $urgency;
	    } else {
		    return '';
	    }
    }

	/**
	 * Gets the source from the XML and returns it as XML String.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param DOMDocument $xml The DOM Tree of the file to parse.
	 *
	 * @return string The urgency if found, otherwise an empty string.
	 */
	public function get_source_from_newsml( $xml ) {
		$xpath = $this->generate_xpath_on_xml( $xml );

		// Get all media topics
		$query_source = '//tempNS:service[contains(@qcode, "apasvc:")]/tempNS:name';
		$result_source = $xpath->query( $query_source );

		$source = $result_source->item( 0 )->nodeValue;

	    if ( $source != '' ) {
		    return $source;
	    } else {
		    return '';
	    }
	}

	/**
     * Gets all mediatopics of the news message and returns them as an array.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return array The mediatopics if found, otherwise an empty array.
     */
    public function get_mediatopics_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        // Get all media topics
        $query_mediatopics = '//tempNS:subject[@type="cpnat:abstract" and contains(@qcode, "medtop:")]';
        $result_mediatopics = $xpath->query( $query_mediatopics );

        $topics = array();

        foreach ( $result_mediatopics as $mediatopic ) {
            $topic = array(
                'name' => $mediatopic->nodeValue,
                'qcode' => $mediatopic->getAttribute( 'qcode' ),
            );
            $topics[] = $topic;
        }

        return $topics;
    }

	/**
	 * Gets all desks of the news message and returns them as an array.
	 *
	 * @author Reinhard Stockinger
	 *
	 * @param DOMDocument $xml The DOM Tree of the file to parse.
	 *
	 * @return array The desks if found, otherwise an empty array.
	 */
	public function get_desks_from_newsml( $xml ) {

		$xpath = $this->generate_xpath_on_xml( $xml );

		// Get all media topics
		$query_desks = '//tempNS:subject[@type="cpnat:abstract" and contains(@qcode, "apadesk:")]';
		$result_desks = $xpath->query( $query_desks );

		$desks = array();

		foreach ( $result_desks as $desk ) {
			$deskcode = $desk->getAttribute( 'qcode' );
			$partarr = explode(":",$deskcode);
			$deskstr = strtoupper($partarr[1]);
			$desks[] = $deskstr;
		}

		return $desks;
	}

    /**
     * Gets all locations of the news message and returns them as an array.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return array The locations if found, otherwise an empty array.
     */
    public function get_locations_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_locations = '//tempNS:subject[@type="cpnat:geoArea" and @why="why:direct"]';
        $result_locations = $xpath->query( $query_locations );

        $geolocations = array();

        foreach ( $result_locations as $location ) {
            $geolocation = array(
                'name' => $location->nodeValue,
                'qcode' => $location->getAttribute( 'qcode' ),
            );
            $geolocations[] = $geolocation;
        }

        return $geolocations;
    }

    /**
     * Extracts the HTML body out of the XML and gets all p and pre elements and returns them as content.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The content if found, otherwise an empty string.
     */
    public function get_content_from_newsml( $xml ) {

        // First extract the body part
        $xml = str_replace( 'default:', '', $xml->saveXML() );
        $preg_ret = preg_match( '/<body>(.*)<\/body>/iUmsu', $xml, $html );
        if ($preg_ret === FALSE || $preg_ret === 0){ //can't find content or error in preg_match!
        	return "";
        }
        // Then load this bodypart into a DOMDocument so we can XPath the elements in it
        $doc = new DOMDocument();
        $doc->loadHTML( mb_convert_encoding( $html[0], 'HTML-ENTITIES', 'UTF-8' ) );

        $xpath = new DOMXpath( $doc );

        $content = "";

        // XPath p and pre to get all content
        foreach ( $path_result = $xpath->query( '//p|//pre' ) as $element ) {
            if ( $element->nodeName == 'p' ) {
                $trimmed_innerhtml = trim( preg_replace( '/\s+/', ' ', $element->nodeValue ) );
                $content .= '<' . $element->nodeName . '>' . $trimmed_innerhtml . '</' . $element->nodeName . '>';

            } else {
                $content .= '<' . $element->nodeName . '>' . $element->nodeValue . '</' . $element->nodeName . '>';
            }
        }

        return $content;
    }

    /**
     * Creates a new XPath element on $file to run XPath queries on it.
     * Also assigns a specific namespace.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $file The file we want to use our XPath queries on.
     * @returns DOMXPath $xpath The XPath element.
     */
    protected function generate_xpath_on_xml( $file ) {

        $xpath = new DOMXPath( $file );
        $xpath->registerNamespace( 'tempNS', 'http://iptc.org/std/nar/2006-10-01/' );

        return $xpath;
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

// body neuen namespace um auf html zugreifen zu können
// siehe newsml_g2.pm zeile 94


