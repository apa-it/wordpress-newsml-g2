<?php

require_once( 'class-newsml-parser.php' );
require_once( 'class-newsml-kap-parser.php' );
require_once( 'class-newsml-reuters-parser.php' );

/**
 * Class Parser_Chooser
 */
class Parser_Chooser {

    /**
     * The list of available parsers which can be used.
     * @var array $_parser_list
     */
    private $_parser_list = array(
        'NewsML_Parser',
        'NewsML_KAP_Parser',
        'NewsML_Reuters_Parser',
    );

    /**
     * Loops through all available parsers and calls can_parse of the particular parser to check if the object is parsable by the parser.
     *
     * @author Bernhard Punz
     *
     * @param $dom_object
     * @return mixed
     */
    public function choose_parser( $dom_object ) {

        foreach ( $this->_parser_list as $p ) {
            if ( class_exists( $p ) ) {
                $parser = new $p();
                if ( $parser->can_parse( $dom_object ) ) {
                    return $parser;
                }
            }
        }

    }
}

