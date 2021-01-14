<?php

require_once( 'class-newsml-parser.php' );

/**
 * Provides the capability to parse a XML file containing NewsML-G2 data from Reuters.
 */
class NewsML_Reuters_Parser extends NewsML_Parser {

    /**
     * The unique identifier which is used by can_parse to determine if the file to parse is from Reuters.
     *
     * @var string $_provider
     */
    private $_provider = 'reuters.com';

    /**
     * Checks if the DOM Object is from Reuters and parsable by this parser.
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

        if ($item = $result->item( 0 )) {
            if ( $item->getAttribute( 'literal' ) == $this->_provider ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parses the DOM Object, fetches all required data and from it and returns them as NewsML_Object.
     * The difference to the method in the NewsML_Parser class is a different attribute value to check.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $file The DOM Tree of the file to parse.
     *
     * @return NewsML_Object The filled NewsML_Object.
     */
    public function parse( $file ) {

        // Create a new NewsML_Object that stores all our data and will be added later to an array
        $news_object = new NewsML_Object();

        // Generate the XPath
        $xpath = $this->generate_xpath_on_xml( $file );
        $query = '//tempNS:newsMessage/tempNS:itemSet'; // all itemSet
        $result = $xpath->query( $query );

        $guid = $copyrightholder = $copyrightnotice = $timestamp = $content = '';
        $titles = array_fill_keys( array( 'title', 'subtitle' ), '');
        $mediatopics = $locations = array();

        // We loop through all itemSets, those can be a packageItem or newsItem
        foreach ( $result->item( 0 )->childNodes as $child ) { // packageItem, newsItem

            // Now we need to get the itemClass so we can differ between pictures and text
            $var = $child->getElementsByTagName( 'itemClass' )->item( 0 );

            if ( $var->getAttribute( 'qcode' ) == 'icls:text' ) {

                $textitem = $var->parentNode->parentNode;

                $doc = new DOMDocument();
                $doc->formatOutput = true;
                $doc->loadXML( '<root></root>' );
                $doc->preserveWhiteSpace = false;

                $to_import = $doc->importNode( $textitem, true );
                $doc->documentElement->appendChild( $to_import );

                $doc_to_work = $doc->saveXML();

                $guid = $this->get_guid_from_newsml( $doc );
                $copyrightholder = $this->get_copyrightholder_from_newsml( $doc );
                $copyrightnotice = $this->get_copyrightnotice_from_newsml( $doc );
                $timestamp = $this->get_datetime_from_newsml( $doc );
                $titles = $this->get_titles_from_newsml( $doc );
                $mediatopics = $this->get_mediatopics_from_newsml( $doc );
                $content = $this->get_content_from_newsml( $doc );
                $locations = $this->get_locations_from_newsml( $doc );
            }
        }

        $news_object->set_guid( $guid );
        $news_object->set_timestamp( $timestamp );
        $news_object->set_copyrightholder( $copyrightholder );
        $news_object->set_copyrightnotice( $copyrightnotice );
        $news_object->set_title( $titles['title'] );
        $news_object->set_subtitle( $titles['subtitle'] );
        $news_object->set_mediatopics( $mediatopics );
        $news_object->set_locations( $locations );
        $news_object->set_content( $content );

        return $news_object;
    }

    /**
     * Gets the title and subtitle of the news message and returns them as an array.
     * The difference to the method in the NewsML_Parser class is a different xpath query.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return array The titles if found, otherwise an array with empty values.
     */
    public function get_titles_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_title = '//tempNS:headline';
        $result_title = $xpath->query( $query_title );

        $title = "";
        if ( $result_title->length > 0 ) {
            $title = $result_title->item(0)->nodeValue;
        }

        $query_subtitle = '//tempNS:headline[@role="hlrole:sub"]';
        $result_subtitle = $xpath->query( $query_subtitle );

        $subtitle = "";
        if ( $result_subtitle->length > 0 ) {
            $subtitle = $result_subtitle->item(0)->nodeValue;
        }

        $all_titles = array(
            'title' => $title,
            'subtitle' => $subtitle,
        );

        return $all_titles;
    }

    /**
     * Gets the copyrightholder information and returns it.
     * The difference to the method in the NewsML_Parser class is a different xpath query.
     *
     * @author Bernhard Punz
     *
     * @param DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return string The copyrightholder if found, otherwise an empty string.
     */
    public function get_copyrightholder_from_newsml( $xml ) {

        $xpath = $this->generate_xpath_on_xml( $xml );

        $query_holder = '//tempNS:rightsInfo/tempNS:copyrightHolder';
        $result_holder = $xpath->query( $query_holder );

        $holder = $result_holder->item(0)->getAttribute('literal');

        return $holder;
    }

    /**
     * Gets all mediatopics of the news message and returns them as an array.
     * The differnece to the method in the NewsML_parser class is that Reuters does not use mediatopics.
     * So this method uses the subjects in the NewsML which is the most similar to the mediatopics.
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
        $query_mediatopics = '//tempNS:subject[contains(@qcode, "subj:")]';
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
}

