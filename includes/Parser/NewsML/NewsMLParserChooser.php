<?php

namespace NewsML_G2\Plugin\Parser\NewsML;

/**
 * Class Parser_Chooser
 */
class NewsMLParserChooser
{
    /**
     * The list of available parsers which can be used.
     * @var array $_parser_list
     */
    private $_parser_list = array(
        '\NewsML_G2\Plugin\Parser\NewsML\NewsMLParser',
        '\NewsML_G2\Plugin\Parser\NewsML\Vendor\NewsMLParserInnodata',
        '\NewsML_G2\Plugin\Parser\NewsML\Vendor\NewsMLParserKAP',
        '\NewsML_G2\Plugin\Parser\NewsML\Vendor\NewsMLParserReuters',
    );

    /**
     * Loops through all available parsers and calls can_parse of the particular parser to check if the object is parsable by the parser.
     *
     * @param $dom_object
     * @return mixed
     * @author Bernhard Punz
     *
     */
    public function choose_parser($dom_object)
    {
        foreach ($this->_parser_list as $p) {
            if (class_exists($p)) {
                $parser = new $p();
                if ($parser->can_parse($dom_object)) {
                    return $parser;
                }
            }
        }

        return false;
    }
}
