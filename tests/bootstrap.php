<?php
session_start();
require_once dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/includes/functions.php';
//require_once '../../../../includes/functions.php';

function _manually_load_environment() {

    // Add your theme …
//    switch_theme('your-theme-name');

    // Update array with plugins to include ...
    $plugins_to_active = array(
        'newsml-g2-importer/newsmlg2import.php'
    );

    update_option( 'active_plugins', $plugins_to_active );

}

tests_add_filter( 'muplugins_loaded', '_manually_load_environment' );

require dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/includes/bootstrap.php';