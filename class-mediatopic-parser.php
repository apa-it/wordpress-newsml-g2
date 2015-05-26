<?php

require_once( 'class-mediatopic-object.php' );

/**
 * This class parses the mediatopics stored on the server of the IPTC and creates a list of Mediatopics (Mediatopic Object)
 */
class Mediatopic_Parser {

    // This URL should not be changed because we always want the newest version
    private $url = 'http://cv.iptc.org/newscodes/mediatopic/?format=g2ki';

    /**
     * Loads the file located in $url and parses everything and creates a list of Mediatopics.
     *
     * @author Bernhard Punz
     *
     * @param string $lang The language which mediatopics are loaded.
     *
     * @return array An array containing Mediatopic Objects with all mediatopics loaded.
     */
    public function load( $lang ) {

        // A few options for the HTTP-Request we are sending via file_get_contents()
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Accept-language: ' . $lang,
            )
        );

        $headers = stream_context_create( $opts );

        // Get the XML from $url
        $res = file_get_contents( $this->url, false, $headers );

        // Create a new DOMDocument containing the data we got
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML( $res );

        $xpath = new DOMXPath( $doc );
        $xpath->registerNamespace( 'tempNS', 'http://iptc.org/std/nar/2006-10-01/' );

        // Get all nodes called "concept"
        $query = '//tempNS:conceptSet/tempNS:concept';
        $xres = $xpath->query( $query );

        $topics = array();

        // Loop through all nodes and create a Mediatopic object
        foreach ( $xres as $r ) {

            if ( strlen( $r->getAttribute( 'modified' ) ) > 0 ) {
                $modified = trim( strtotime( $r->getAttribute( 'modified' ) ) );
            } else {
                $modified = 0;
            }

            if ( $r->getElementsByTagName( 'type' )->length > 0 ) {
                $type = trim( $r->getElementsByTagName( 'type' )->item( 0 )->getAttribute( 'qcode' ) );
            } else {
                $type = '';
            }

            if ( $r->getElementsByTagName( 'name' )->length > 0 ) {
                $name = trim( $r->getElementsByTagName( 'name' )->item( 0 )->nodeValue );
            } else {
                $name = 'no name found';
            }
            if ( $r->getElementsByTagName( 'conceptId' )->length > 0 ) {
                $qcode = trim( $r->getElementsByTagName( 'conceptId' )->item( 0 )->getAttribute( 'qcode' ) );
            } else {
                $qcode = '';
            }
            if ( $r->getElementsByTagName( 'definition' )->length > 0 ) {
                $definition = trim( $r->getElementsByTagName( 'definition' )->item( 0 )->nodeValue );
            } else {
                $definition = '';
            }

            // We want to get all broader concepts, so we have to create a new DOMDocument from the DOMElement we got previously
            $doc2 = new DOMDocument();
            $doc2->formatOutput = true;
            $doc2->loadXML( '<root></root>' );
            $doc2->preserveWhiteSpace = false;

            $to_import = $doc2->importNode( $r, true );
            $doc2->documentElement->appendChild( $to_import );

            $xpath_broader = new DOMXPath( $doc2 );
            $xpath_broader->registerNamespace( 'tempNS', 'http://iptc.org/std/nar/2006-10-01/' );

            $query_broader = '//tempNS:broader';
            $res_broader = $xpath_broader->query( $query_broader );

            $broaders = array();

            // Parses all broader mediatopics
            foreach ( $res_broader as $rr ) {
                $broaders[] = $rr->getAttribute( 'qcode' );
            }

            $obj = new Mediatopic();

            $obj->set_qcode( $qcode );
            $obj->set_modified( $modified );
            $obj->set_type( $type );
            $obj->set_name( $name );

            foreach ( $broaders as $b ) {
                $obj->add_broader( $b );
            }

            $obj->set_definition( $definition );

            $topics[] = $obj;
            unset( $obj );
        }

        return $topics;
    }
}