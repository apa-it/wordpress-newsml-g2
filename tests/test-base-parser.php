<?php

class Parser_Base_Test extends WP_UnitTestCase  {

    private $_file;
    private $_parser;
    private $_xml;

    public function setUp() {
        $this->_file = file_get_contents( dirname( __FILE__ ) . '/assets/apa1.xml' );
        $this->_parser = new NewsML_Parser();

        $this->_xml = new DOMDocument();
        $this->_xml->preserveWhiteSpace = false;
        $this->_xml->loadXML( $this->_file );
    }

    public function test_Base_Parser_can_parse_successful() {

        $res = $this->_parser->can_parse( $this->_xml );

        $this->assertTrue( $res );
    }

    public function test_Base_Parser_Object_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertNotEmpty( $res );
    }

    public function test_Base_Parser_GUID_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'urn:newsml:apa.at:20150413:APA0520', $res->get_guid() );
    }

    public function test_Base_Parser_Timestamp_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( '1429167454', $res->get_timestamp() );
    }

    public function test_Base_Parser_Copyrightholder_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'APA - Austria Presse Agentur', $res->get_copyrightholder() );
    }

    public function test_Base_Parser_Copyrightnotice_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'Die APA - Austria Presse Agentur hat das alleinige Recht auf
                    Verbreitung ihrer Meldungen und Dienste. Die Bezieher sind berechtigt,
                    diese im Rahmen der jeweils gültigen Verträge zu nutzen.  Jegliche
                    darüber hinaus gehende Nutzung, insbesonders die Weitergabe an Dritte,
                    ist untersagt.', $res->get_copyrightnotice() );
    }

    public function test_Base_Parser_Title_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( 'Emmanuelle Bercot eröffnet das Filmfestival von Cannes', $res->get_title() );
    }

    public function test_Base_Parser_Subtitle_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $this->assertEquals( '"La tete haute" der französischen Regisseurin als Eröffnungsfilm ausgewählt', $res->get_subtitle() );
    }

    public function test_Base_Parser_Mediatopics_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $medtops = $res->get_mediatopics();

        $this->assertEquals( 'Kunst & Kultur', $medtops[0]['name'] );
        $this->assertEquals( 'medtop:01000000', $medtops[0]['qcode'] );
        $this->assertEquals( 'Kunst', $medtops[1]['name'] );
        $this->assertEquals( 'medtop:20000002', $medtops[1]['qcode'] );
        $this->assertEquals( 'Kino', $medtops[2]['name'] );
        $this->assertEquals( 'medtop:20000005', $medtops[2]['qcode'] );
        $this->assertEquals( 'Filmfestival', $medtops[3]['name'] );
        $this->assertEquals( 'medtop:20000006', $medtops[3]['qcode'] );
    }

    public function test_Base_Parser_Locations_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $locations = $res->get_locations();

        $this->assertEquals( 'Paris', $locations[0]['name'] );
        $this->assertEquals( 'apageo:4254004', $locations[0]['qcode'] );
        $this->assertEquals( 'Cannes', $locations[1]['name'] );
        $this->assertEquals( 'apageo:4259755', $locations[1]['qcode'] );
    }

    public function test_Base_Parser_Content_successfully_parsed() {

        $res = $this->_parser->parse( $this->_xml );

        $content = $res->get_content();

        $this->assertEquals( trim( '<pre>---------------------------------------------------------------------
                                KORREKTUR-HINWEIS
                                In APA0520 vom 13.04.2015 muss im Titel und ersten Absatz gestrichen
                                werden, dass Bercot als erste Frau das Filmfestival in Cannes
                                eröffnet. Das Festival hat seine Angaben korrigiert. 1987 startete
                                das Festival mit "Leidenschaftliche Begegnung" von Diane Kurys, wie
                                im zweiten Absatz ergänzt wurde.
                                ---------------------------------------------------------------------
                            </pre><p>Paris/Cannes (APA/dpa) - Das Filmfest Cannes startet in diesem Jahr mit einem Werk der Französin Emmanuelle Bercot. Das teilten die Festspiele am Montag mit. "La tete haute" (etwa: Erhobenen Hauptes) erzählt die Geschichte des jungen Kleinkriminellen Malony, den eine Jugendrichterin wieder auf den richtigen Weg bringen will.</p><p>"Die Wahl des Films mag überraschen. Doch wir wollten das Festival mit einem etwas anderen Film eröffnen", heißt es in der Pressemitteilung weiter. Das Festival findet vom 13. bis 24. Mai statt. Das Filmfest war in den Vorjahren immer wieder kritisiert worden, weil im Wettbewerb kaum Filme von Regisseurinnen liefen. 1987 war das Festival mit "Leidenschaftliche Begegnung" von Diane Kurys eröffnet worden. Emmanuelle Bercot ist damit nun die zweite Frau, deren Film zum Auftakt gezeigt wird.</p><p>In der Milieustudie "La tete haute" spielt auch Catherine Deneuve mit. Der Leinwandstar drehte mit der 47-jährigen Bercot bereits "Madame empfiehlt sich". Die Filme, die dieses Jahr in Cannes ins Rennen um die Goldene Palme kommen, werden an diesem Donnerstag (16. April) in Paris bekanntgegeben. Ob der Eröffnungsfilm in oder außerhalb des Wettbewerbs laufen wird, teilten die Organisatoren nicht mit.</p><p>(S E R V I C E - www.festival-cannes.com)</p>' ), $content );
    }
}