<?php

class NewsMLPluginTest extends WP_UnitTestCase {

    private $_plugin;

    public function setUp() {
        $this->_plugin = new NewsMLG2_Importer_Plugin();
    }

    function test_Plugin_successfully_loaded() {
        $this->assertTrue( is_plugin_active( 'newsml-g2-importer/newsmlg2import.php' ) );
    }
}