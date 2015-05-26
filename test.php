<?php

require_once( "class-parser-chooser.php" );
//require_once( "class-newsml-parser.php" );
//require_once( "class-newsml-kap-parser.php" );
//require_once( "class-newsml-reuters-parser.php" );
require_once( 'interface-file-access.php' );
require_once( 'class-http-file-access.php' );
require_once( 'class-ftp-file-access.php' );

echo __DIR__;
echo "<br>start: " . time() . "<br>";


//$path = 'http://crsle1.crs.apa.at/wordpress-g2/apa_ots/';
$path = 'ftp://crsle1.crs.apa.at/';

//$path = 'http://localhost/newsmltest/';
$count = 10;

$username = 'crsplwordpressg2';
$password = 'smhKo7MRSLzF27';

//$access = new HTTP_File_Access( $path, '', '' );
$access = new FTP_File_Access( $path, $username, $password );

$access->establish_connection();
$list = $access->file_list();

$list = array_reverse( $list );

$access->save_files( $list );


$news_objects = array();

foreach ( $list as $df ) {
    $file = $access->open_file( $df );

    $xml = new DOMDocument();
    $xml->preserveWhiteSpace = false;
    $xml->loadXML( $file );


    $chooser = new Parser_Chooser();

    $parser = $chooser->choose_parser( $xml );

    if ( $parser ) {
        $obj = $parser->parse( $xml );
    }

    $news_objects[] = $obj;
}

$access->recursive_rmdir();


foreach ( $news_objects as $object ) {
    echo '<h1>' . $object->get_title() . '</h1>';
    echo '<h2>' . $object->get_subtitle() . '</h2>';

    echo date( 'd.m.y H:i', (int)$object->get_timestamp() );

    echo '<br><strong>Mediatopics:</strong> ';
    foreach ( $object->get_mediatopics() as $topic ) {
        echo $topic['name'] . ', ';
    }

    echo '<br><strong>Location:</strong> ';
    foreach ( $object->get_locations() as $location ) {
        echo $location['name'] . ', ';
    }

    echo $object->get_content();

    foreach ( $object->get_multimedia() as $data ) {
        echo '<img src="' . $data['href'] . '" alt="' . $data['href'] . '">';
    }
//    echo "<pre>" . print_r( $object, true ) . "</pre>";
}
echo "<br>end: " . time() . "<br>";

?>