<?php

namespace NewsML_G2\Plugin\Parser\NewsML\Vendor;

use NewsML_G2\Plugin\Parser\NewsML\NewsMLParser;

/**
 * Provides the capability to parse a XML file containing NewsML-G2 data from Kathpress.
 */
class NewsMLParserKAP extends NewsMLParser
{
    /**
     * The unique identifier which is used by can_parse to determine if the file to parse is from Kathpress.
     *
     * @var string $_provider
     */
    private $_provider = 'kathpress.at';

    /**
     * Checks if the DOM Object is from Kathpress and parsable by this parser.
     *
     * @param \DOMDocument $file The DOM Tree of the file to parse.
     *
     * @return bool True if the file is parsable by this parser.
     * @author Bernhard Punz
     *
     */
    public function can_parse($file)
    {
        $xpath = $this->generate_xpath_on_xml($file);
        $query = '//tempNS:newsMessage/tempNS:itemSet/tempNS:newsItem/tempNS:itemMeta/tempNS:provider';
        $result = $xpath->query($query);

        if ($item = $result->item(0)) {
            if ($item->getAttribute('literal') == $this->_provider) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the title and subtitle of the news message and returns them as an array.
     * The difference to the method in the NewsML_Parser class is a different xpath query.
     *
     * @param \DOMDocument $xml The DOM Tree of the file to parse.
     *
     * @return array The titles if found, otherwise an array with empty values.
     * @author Bernhard Punz
     *
     */
    public function get_titles_from_newsml($xml)
    {
        $xpath = $this->generate_xpath_on_xml($xml);

        $query_title = '//tempNS:headline[@role="hlrole:main"]';
        $result_title = $xpath->query($query_title);

        $title = "";
        if ($result_title->length > 0) {
            $title = $result_title->item(0)->nodeValue;
        }

        $query_subtitle = '//tempNS:headline[@role="hlrole:sub"]';
        $result_subtitle = $xpath->query($query_subtitle);

        $subtitle = "";
        if ($result_subtitle->length > 0) {
            $subtitle = $result_subtitle->item(0)->nodeValue;
        }

        $all_titles = array(
            'title' => $title,
            'subtitle' => $subtitle,
        );

        return $all_titles;
    }
}
