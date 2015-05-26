<?php
/**
 * Plugin Name: NewsML-G2-Importer
 * Plugin URI: http://apa-it.at/
 * Description: Imports NewsML-G2 data (APA, APA-OTS, Kathpress and Thomson Reuters) and inserts them as NewsML Post which is accessible via http://your.blog/news. The access to the NewsML-G2 files is possbile via HTTP and FTP. Every 5 minutes the folder is parsed again and the news get updated. <strong>Warning: </strong> After the activation of the plugin you will be redirected to the settings to configure the plugin.
 * Version: 1.0
 * Author: Bernhard Punz
 * Author URI: -
 * License: GPLv2
 * Text Domain: newsml-import
 * Domain Path: languages/
 */

session_start();
ini_set( 'max_execution_time', 0 );

// Used for the german translation of the plugin description
$desc = __( 'Imports NewsML-G2 data (APA, APA-OTS, Kathpress and Thomson Reuters) and inserts them as NewsML Post which is accessible via http://your.blog/news. The access to the NewsML-G2 files is possbile via HTTP and FTP. Every 5 minutes the folder is parsed again and the news get updated. <strong>Warning: </strong> After the activation of the plugin you will be redirected to the settings to configure the plugin.', 'newsml-import' );

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );
define( 'NEWSML_FILE', __FILE__ );

require_once( "class-parser-chooser.php" );
require_once( 'interface-file-access.php' );
require_once( 'class-http-file-access.php' );
require_once( 'class-ftp-file-access.php' );
require_once( 'class-mediatopic-parser.php' );
require_once( 'class-mediatopic-object.php' );
require_once( 'class-rss-parser.php' );
/* Those includes are needed because the cron sometimes needs to upload images
 * and it seems that media_sideload_image isn't available without those includes
 * or a finished admin_init hook.
*/
require_once( ABSPATH . 'wp-admin/includes/media.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

/**
 * Class NewsML_Importer_Plugin
 */
class NewsMLG2_Importer_Plugin {

    /**
     * The name of the options for the plugin as they are saved in the database.
     *
     * @var string $option_name
     */
    private $option_name = 'newsml-import';

    private $_home_path = '';

    /**
     * The default values for the options page
     *
     * @var array $data
     */
    private $data = array(
        'url_newsml' => '',
        'enable_ftp' => 'no',
        'ftp_user' => '',
        'ftp_pass' => '',
        'image_dir' => 'wp-content/newsml-images',
        'expire_time' => '10',
        'use_rss' => '',
        'news_frontpage' => '',
        'kiosk_count' => '5',
        'kiosk_duration' => '20',
    );

    /**
     * Registers numerous actions, hooks and filters.
     */
    function __construct() {

        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_action( 'admin_menu', array( $this, 'add_page' ) );

        register_activation_hook( NEWSML_FILE, array( $this, 'activate' ) );

        register_deactivation_hook( NEWSML_FILE, array( $this, 'deactivate' ) );

        add_action( 'init', array( $this, 'register_taxonomy_mediatopics' ) );
        add_action( 'init', array( $this, 'register_posttype_newsmlpost' ) );
        add_action( 'add_meta_boxes', array( $this, 'newsmlpost_meta_init' ) );

        add_action( 'save_post', array( $this, 'save_newsmlpost_meta' ) );

        // Actions for additional fields for the taxonomies
        add_action( 'newsml_mediatopic_add_form_fields', array( $this, 'newsml_mediatopic_taxonomy_add_new_meta_field' ), 10, 2 );
        add_action( 'newsml_mediatopic_edit_form_fields', array( $this, 'newsml_mediatopic_taxonomy_edit_meta_field' ), 10, 2 );
        add_action( 'edited_newsml_mediatopic', array( $this, 'save_newsml_mediatopic_custom_meta' ), 10, 2 );
        add_action( 'create_newsml_mediatopic', array( $this, 'save_newsml_mediatopic_custom_meta' ), 10, 2 );

        // Action for the cron
        add_filter( 'cron_schedules', array( $this, 'add_five_minutes_interval' ) );
        add_action( 'newsml_update_delete_news_cron', array( $this, 'cron_update_delete' ) );

        // Load the textdomain
        add_action( 'plugins_loaded', array( $this, 'load_newsml_textdomain' ) );

        // Add filters for custom view in archive and single page view
        add_filter( 'the_content', array( $this, 'remove_content_from_archive' ) );
        add_filter( 'newsml_include_filter', array( $this, 'show_metadata_on_single_newsml' ) );

        // Hide the deprecated posts (posts with older versions)
        add_action( 'pre_get_posts', array( $this, 'hide_deprecated_posts' ) );

        // Wanna show newsml_posts on the frontpage?
        $result = get_option( $this->option_name );
        if ( $result['news_frontpage'] == 'yes' ) {
            add_action( 'pre_get_posts', array( $this, 'add_newsml_on_front_page' ) );
        }

        // Add the settings link to the plugin view
        $plugin = plugin_basename( __FILE__ );
        add_filter( "plugin_action_links_$plugin", array( $this, 'plugin_add_settings_link' ) );

    }

    /**
     * Adds a link to the settings page on the plugin list.
     * @param $links
     * @return mixed
     */
    public function plugin_add_settings_link( $links ) {
        $settings_link = '<a href="options-general.php?page=newsml-list-options">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    /**
     * Hides posts with meta_key newsml_meta_deprecated = yes.
     *
     * @author Bernhard Punz
     *
     * @param $query
     * @return mixed
     */
    public function hide_deprecated_posts( $query ) {
        $post_types = array( 'newsml_post' );

        if ( is_post_type_archive( $post_types ) && ! is_single() && ! is_admin() ) {

            $meta_query = $query->get( 'meta_query' );

            $meta_query[] = array(
                'key' => 'newsml_meta_deprecated',
                'value' => 'yes',
                'compare' => '!=',
            );
            $query->set( 'meta_query', $meta_query );
        }
        return $query;
    }


    /**
     * Adds, if checked, NewsML Posts on the front page.
     *
     * @author Bernhard Punz
     *
     * @param $query
     * @return mixed
     */
    public function add_newsml_on_front_page( $query ) {
        if ( is_home() && $query->is_main_query() )
            $query->set( 'post_type', array( 'post', 'newsml_post' ) );
        return $query;
    }

    /**
     * Removes the content of the post if shown in the archive page.
     *
     * @author Bernhard Punz
     *
     * @param $template
     * @return string Returns an empty string if the content should be removed, otherwise returns the normal template.
     */
    public function remove_content_from_archive( $template ) {
        global $post;
        $post_types = array( 'newsml_post' );
        if ( is_post_type_archive( $post_types ) && ! is_single() || count( wp_get_object_terms( $post->ID, 'mediatopic' ) ) > 0 && ! is_single() && ! is_home() ) {
            return "";
        } else {
            return $template;
        }
    }

    /**
     * Includes the metadatafile if the user is on a single page.
     *
     * @author Bernhard Punz
     */
    public function show_metadata_on_single_newsml() {
        global $post;

        if ( $post->post_type == 'newsml_post' && is_single() || $post->post_type == 'newsml_post' && is_home()
        ) {
            include( ABSPATH . 'wp-content/plugins/newsml-g2-importer/include-metadata.php' );
        }
    }

    /**
     * Loads the desired textdomain for i18n
     *
     * @author Bernhard Punz
     */
    function load_newsml_textdomain() {
        load_plugin_textdomain( 'newksml-import', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Inserts the news from the array with NewsML_Objects into the database.
     *
     * @author Bernhard Punz
     */
    public function insert_news_to_db() {

        global $wpdb;

        $result = get_option( $this->option_name );

        // Do we want to use FTP or HTTP
        if ( $result['enable_ftp'] == 'yes' ) {
            $access = new FTP_File_Access( $result['url_newsml'], $result['ftp_user'], $result['ftp_pass'] );
        } else {
            $access = new HTTP_File_Access( $result['url_newsml'], '', '' );
        }

        $access->establish_connection();

        // Get the filelist from the database to see which files we already read
        $files = get_option( 'newsml-filelist' );
        $files = json_decode( $files );

        if ( $result['use_rss'] == 'yes' ) {

            $rss = new RSS_Parser( $access );

            // Determine the new files we want to add
            if ( ! empty( $files ) ) {
                $diff = array_diff( $rss->file_list(), $files );
            } else {
                $diff = $rss->file_list();
            }

            $diff = array_values( $diff );
        } else {
            // Determine the new files we want to add
            if ( ! empty( $files ) ) {
                $diff = array_diff( $access->file_list(), $files );
            } else {
                $diff = $access->file_list();
            }

            $diff = array_values( $diff );
        }

        $access->save_files( $diff );

        $news_to_add = array();

        // Load all the files with their associated parser and create a NewsML_Object
        foreach ( $diff as $df ) {
            $file = $access->open_file( $df );

            $xml = new DOMDocument();
            $xml->preserveWhiteSpace = false;
            $xml->loadXML( $file );

            $chooser = new Parser_Chooser();

            $parser = $chooser->choose_parser( $xml );

            if ( $parser ) {
                $obj = $parser->parse( $xml );
                $obj->set_filename( $df );
            }

            $news_to_add[] = $obj;
        }

        // Iterate through our news objects and save them to the database
        foreach ( $news_to_add as $object ) {


            // If the version of the object is 1, it is a new post that should not be in the database.
            if ( $object->get_version() == '1' ) {

                $query_check_exists = $wpdb->prepare(
                    "SELECT m.post_id, m.meta_value, p.post_date FROM $wpdb->postmeta m
                  LEFT JOIN $wpdb->posts p ON p.ID = m.post_id
                  WHERE meta_key = 'newsml_meta_guid' AND meta_value =%s",
                    $object->get_guid()
                );

                // Check if the post already exists, if not, insert it
                $res_check_exists = $wpdb->query( $query_check_exists );

                if ( $res_check_exists <= 0 ) {
                    $new_post_id = wp_insert_post( array(
                        'post_type' => 'newsml_post',
                        'post_name' => sanitize_title( $object->get_title() ),
                        'post_title' => $object->get_title(),
                        'post_content' => $object->get_content(),
                        'post_status' => 'publish',
                        'post_date' => date( 'Y-m-d H:i:s', $object->get_timestamp() ),
                        'post_date_gmt' => date( 'Y-m-d H:i:s', $object->get_timestamp() ),
                    ) );

                    // Just try changing the mediatopics if the post was successfully created
                    if ( $new_post_id ) {

                        $tax_ids = array();

                        $mediatopics = $object->get_mediatopics();
                        if ( ! empty( $mediatopics ) ) {
                            foreach ( $mediatopics as $topic ) {

                                $res_parent = $wpdb->get_row( "SELECT option_name, option_value FROM  $wpdb->options  WHERE option_value LIKE '%s:5:\"qcode\";s:15:\"" . $topic['qcode'] . "%'", ARRAY_A );

                                if ( ! empty( $res_parent ) ) {
                                    // Get the ID of the parent mediatopic that is saved in term_taxonomy
                                    $splitted_taxonomy_meta = explode( '_', $res_parent['option_name'] );

                                    // Get the acutal parent term
                                    $parent_term = get_term( $splitted_taxonomy_meta[2], 'newsml_mediatopic', ARRAY_A );

                                    // Update the child mediatopic, now with the correct parent ID
                                    $tax_ids[] = $parent_term['term_id'];
                                }
                            }
                        }

                        $tax_ids = array_map( 'intval', $tax_ids );
                        $tax_ids = array_unique( $tax_ids );

                        $term_tax_ids = wp_set_object_terms( $new_post_id, $tax_ids, 'newsml_mediatopic' );

                        // Insert different post meta
                        $subtitle = $object->get_subtitle();
                        if ( ! empty( $subtitle ) ) {
                            add_post_meta( $new_post_id, 'newsml_meta_subtitle', $object->get_subtitle() );
                        }

                        $locs = $object->get_locations();
                        if ( ! empty( $locs ) ) {
                            $locations = array();
                            foreach ( $locs as $loc ) {
                                $locations[] = $loc['name'];
                            }
                            add_post_meta( $new_post_id, 'newsml_meta_location', implode( ', ', $locations ) );
                        }

                        $access->save_media_files( $this->_home_path . $result['image_dir'], $object->get_multimedia() );

                        $multis = $object->get_multimedia();
                        foreach ( $multis as $file ) {
                            $image = media_sideload_image( home_url() . '/' . $result['image_dir'] . '/' . $file['href'], $new_post_id, 'image for ' . $object->get_title() );
                        }

                        add_post_meta( $new_post_id, 'newsml_meta_guid', $object->get_guid() );
                        add_post_meta( $new_post_id, 'newsml_meta_version', $object->get_version() );
                        add_post_meta( $new_post_id, 'newsml_meta_deprecated', 'no' );
                        add_post_meta( $new_post_id, 'newsml_meta_copyrightholder', $object->get_copyrightholder() );
                        add_post_meta( $new_post_id, 'newsml_meta_copyrightnotice', $object->get_copyrightnotice() );

                        // Add to filelist
                        $files[] = $object->get_filename();

                    } elseif ( is_wp_error( $new_post_id ) ) {
                        $error_string = $new_post_id->get_error_message();
                    }
                } else {
                    /*
                     * Somehow we came here, but we should not be here. So the version was 1 but there was already a post with the same guid.
                     * So the author did something wrong, not my fault!
                    */
                }
            } else {
                // UPDATE
                // The version is greather than 1, so it is an update for some older post.
                $query_check_exists = $wpdb->prepare(
                    "SELECT m.post_id, m.meta_value, p.post_date FROM $wpdb->postmeta m
                  LEFT JOIN $wpdb->posts p ON p.ID = m.post_id
                  WHERE meta_key = 'newsml_meta_guid' AND meta_value =%s
                  ORDER BY m.meta_id DESC LIMIT 1",
                    $object->get_guid()
                );

                // Check if the post already exists, if yes, set the old one to deprecated and insert the new one.
                $res_check_exists = $wpdb->query( $query_check_exists );

                // The post already exists, so we want to update it.
                if ( $res_check_exists > 0 ) {
                    $row = $wpdb->get_row( $query_check_exists, ARRAY_A );

                    // Set the old post to deprecated
                    update_post_meta( $row['post_id'], 'newsml_meta_deprecated', 'yes' );

                    // Insert the new and updated post
                    $new_post_id = wp_insert_post( array(
                        'post_type' => 'newsml_post',
                        'post_name' => sanitize_title( $object->get_title() ),
                        'post_title' => $object->get_title(),
                        'post_content' => $object->get_content(),
                        'post_status' => 'publish',
                        'post_date' => date( 'Y-m-d H:i:s', $object->get_timestamp() ),
                        'post_date_gmt' => date( 'Y-m-d H:i:s', $object->get_timestamp() ),
                    ) );

                    // Just try changing the mediatopics if the post was successfully created
                    if ( $new_post_id ) {

                        $tax_ids = array();

                        $mediatopics = $object->get_mediatopics();
                        if ( ! empty( $mediatopics ) ) {
                            foreach ( $mediatopics as $topic ) {

                                $res_parent = $wpdb->get_row( "SELECT option_name, option_value FROM  $wpdb->options  WHERE option_value LIKE '%s:5:\"qcode\";s:15:\"" . $topic['qcode'] . "%'", ARRAY_A );

                                if ( ! empty( $res_parent ) ) {
                                    // Get the ID of the parent mediatopic that is saved in term_taxonomy
                                    $splitted_taxonomy_meta = explode( '_', $res_parent['option_name'] );

                                    // Get the acutal parent term
                                    $parent_term = get_term( $splitted_taxonomy_meta[2], 'newsml_mediatopic', ARRAY_A );

                                    // Update the child mediatopic, now with the correct parent ID
                                    $tax_ids[] = $parent_term['term_id'];
                                }
                            }
                        }

                        $tax_ids = array_map( 'intval', $tax_ids );
                        $tax_ids = array_unique( $tax_ids );

                        $term_tax_ids = wp_set_object_terms( $new_post_id, $tax_ids, 'newsml_mediatopic' );

                        // Insert different post meta
                        $subtitle = $object->get_subtitle();
                        if ( ! empty( $subtitle ) ) {
                            add_post_meta( $new_post_id, 'newsml_meta_subtitle', $object->get_subtitle() );
                        }

                        $locs = $object->get_locations();
                        if ( ! empty( $locs ) ) {
                            $locations = array();
                            foreach ( $locs as $loc ) {
                                $locations[] = $loc['name'];
                            }
                            add_post_meta( $new_post_id, 'newsml_meta_location', implode( ', ', $locations ) );
                        }

                        $access->save_media_files( $this->_home_path . $result['image_dir'], $object->get_multimedia() );

                        $multis = $object->get_multimedia();
                        foreach ( $multis as $file ) {
                            $image = media_sideload_image( home_url() . '/' . $result['image_dir'] . '/' . $file['href'], $new_post_id, 'image for ' . $object->get_title() );
                        }

                        add_post_meta( $new_post_id, 'newsml_meta_guid', $object->get_guid() );
                        add_post_meta( $new_post_id, 'newsml_meta_version', $object->get_version() );
                        add_post_meta( $new_post_id, 'newsml_meta_deprecated', 'no' );
                        add_post_meta( $new_post_id, 'newsml_meta_copyrightholder', $object->get_copyrightholder() );
                        add_post_meta( $new_post_id, 'newsml_meta_copyrightnotice', $object->get_copyrightnotice() );

                        // Add to filelist
                        $files[] = $object->get_filename();

                    } elseif ( is_wp_error( $new_post_id ) ) {
                        $error_string = $new_post_id->get_error_message();
                    }
                }
            }
        }

        // Update the filelist to the new one
        update_option( 'newsml-filelist', json_encode( $files ) );

        // Remove the temp and newsml-images directories
        $access->recursive_rmdir();
        $access->recursive_rmdir( $this->_home_path . $result['image_dir'] );

        // Recreate the newsml-images directory
        if ( ! file_exists( $this->_home_path . $result['image_dir'] ) ) {
            mkdir( $this->_home_path . $result['image_dir'], 0755 );
        }
    }

    /**
     * Checks if there are any posts that are expired and have to be deleted.
     *
     * @author Bernhard Punz
     */
    public function check_delete_expired_posts() {

        global $wpdb;

        $result = get_option( $this->option_name );

        if ( $result['expire_time'] > 0 ) {
            $exp_seconds = $result['expire_time'] * 86400;

            $date_to_delete = time() - $exp_seconds;

            $rows = $wpdb->get_results( "SELECT ID, post_date FROM $wpdb->posts WHERE UNIX_TIMESTAMP(post_date) < '" . $date_to_delete . "' AND post_type = 'newsml_post' ", ARRAY_A );

            foreach ( $rows as $row ) {

                $attachments = get_posts( array(
                        'post_type' => 'attachment',
                        'posts_per_page' => -1,
                        'post_status' => 'any',
                        'post_parent' => $row['ID']
                    )
                );

                foreach ( $attachments as $attachment ) {
                    if ( false === wp_delete_attachment( $attachment->ID, true ) ) {
                    }
                }

                wp_delete_post( $row['ID'], true );
            }
        }
    }

    /**
     * Runs the check to delete expired posts and also runs the check to insert new posts.
     *
     * @author Bernhard Punz
     */
    public function cron_update_delete() {
        $this->check_delete_expired_posts();
        $this->insert_news_to_db();
    }

    /**
     * Register the validation function to be used.
     *
     * @author Bernhard Punz
     */
    public function admin_init() {

        $this->_home_path = get_home_path();


        if ( get_option( 'newsmlimport_do_activation_redirect', false ) ) {
            delete_option( 'newsmlimport_do_activation_redirect' );
            wp_redirect( 'options-general.php?page=newsml-list-options' );
        }

        register_setting( 'newsml-list-options', $this->option_name, array( $this, 'validate_inputs' ) );

        // If the Update news button is clicked
        if ( isset( $_GET['update-posts'] ) && $_GET['update-posts'] == 'true' ) {

            $this->insert_news_to_db();

            $_SESSION['posts_got_updated'] = 'true';

            $this->register_cron_stuff();

            header( 'Location: options-general.php?page=newsml-list-options' );
            exit();
        }

        // Show the message that the newsposts got updated
        if ( $GLOBALS['pagenow'] == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'newsml-list-options' ) {

            if ( isset( $_SESSION['posts_got_updated'] ) && $_SESSION['posts_got_updated'] == 'true' ) {
                echo '<div class="updated"><p>' . __( 'Updated newsposts', 'newsml-import' ) . '</p></div>';
                $_SESSION['posts_got_updated'] = '';
            }
        }

        // If the Check posts to delete button is clicked
        if ( isset( $_GET['check-delete-posts'] ) && $_GET['check-delete-posts'] == 'true' ) {

            $this->check_delete_expired_posts();

            header( 'Location: options-general.php?page=newsml-list-options' );
            exit();
        }

        // If the Import media topics button is clicked
        if ( isset( $_GET['import-mediatopics'] ) && $_GET['import-mediatopics'] == 'true' ) {

            $run_once = get_option( 'newsml-import_medtop_run_once' );

            if ( $run_once != 'yes' ) {

                // Insert the media topics from the xml file
                $this->insert_mediatopics();

                // Update the media topics parents
                $this->update_parents();

                update_option( 'newsml-import_medtop_run_once', 'yes' );
            }

            $_SESSION['mediatopics_got_imported'] = 'true';

            header( 'Location: options-general.php?page=newsml-list-options' );
            exit();
        }

        // Show the message that the media topics got imported
        if ( $GLOBALS['pagenow'] == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'newsml-list-options' ) {

            if ( isset( $_SESSION['mediatopics_got_imported'] ) && $_SESSION['mediatopics_got_imported'] == 'true' ) {
                echo '<div class="updated"><p>' . __( 'Imported mediatopics', 'newsml-import' ) . '</p></div>';
                $_SESSION['mediatopics_got_imported'] = '';
            }
        }
    }

    /**
     * Validate all inputs made on the options page and then save them.
     *
     * @author Bernhard Punz
     *
     * @param array $input An array with all input from the options page to be sanitized.
     * @return array The sanitized $input as array.
     */
    public function validate_inputs( $input ) {

        $valid = array();

        $valid['url_newsml'] = sanitize_text_field( $input['url_newsml'] );
        $valid['image_dir'] = sanitize_text_field( 'wp-content/newsml-images' );
        $valid['expire_time'] = intval( sanitize_text_field( $input['expire_time'] ) );
        $valid['kiosk_duration'] = intval( sanitize_text_field( $input['kiosk_duration'] ) );
        $valid['kiosk_count'] = intval( sanitize_text_field( $input['kiosk_count'] ) );

        if ( isset( $input['enable_ftp'] ) ) {
            $valid['enable_ftp'] = sanitize_text_field( $input['enable_ftp'] );
        } else {
            $valid['enable_ftp'] = sanitize_text_field( '' );
        }

        if ( isset( $input['use_rss'] ) ) {
            $valid['use_rss'] = sanitize_text_field( $input['use_rss'] );
        } else {
            $valid['use_rss'] = sanitize_text_field( '' );
        }

        if ( isset( $input['news_frontpage'] ) ) {
            $valid['news_frontpage'] = sanitize_text_field( $input['news_frontpage'] );
        } else {
            $valid['news_frontpage'] = sanitize_text_field( '' );
        }

        $valid['ftp_user'] = sanitize_text_field( $input['ftp_user'] );

        $valid['ftp_pass'] = sanitize_text_field( $input['ftp_pass'] );

        if ( strlen( $valid['url_newsml'] ) == 0 ) {
            add_settings_error(
                'newsml_url',
                'newsmlurl_texterror',
                __( 'Please enter a valid URL.', 'newsml-import' ),
                'error'
            );

            $valid['url_newsml'] = sanitize_text_field( $this->data['url_newsml'] );
        }

        if ( ! $this->starts_with( $valid['url_newsml'], 'ftp://' ) && $valid['enable_ftp'] == 'yes' ) {
            add_settings_error(
                'enable_ftp',
                'enable_ftp_texterror',
                __( 'Please enter correct URL, starting with ftp://.', 'newsml-import' ),
                'error'
            );

            $valid['enable_ftp'] = sanitize_text_field( '' );
        }

        if ( $this->starts_with( $valid['url_newsml'], 'ftp://' ) && $valid['enable_ftp'] != 'yes' ) {
            add_settings_error(
                'enable_ftp',
                'enable_ftp_texterror',
                __( 'Please enable access via FTP.', 'newsml-import' ),
                'error'
            );

            $valid['url_newsml'] = sanitize_text_field( '' );
        }

        if ( $valid['enable_ftp'] != 'yes' ) {
            $valid['ftp_user'] = sanitize_text_field( '' );
            $valid['ftp_pass'] = sanitize_text_field( '' );
            $valid['enable_ftp'] = sanitize_text_field( '' );
        }

        if ( strlen( $valid['expire_time'] ) == 0 || ! ctype_digit( $valid['expire_time'] ) ) {
            add_settings_error(
                'newsml_expire_time',
                'newsmlurl_texterror',
                __( 'Please enter a valid number.', 'newsml-import' ),
                'error'
            );

            $valid['expire_time'] = sanitize_text_field( $this->data['expire_time'] );
        }

        return $valid;
    }

    /**
     * Add the options page to the menu.
     *
     * @author Bernhard Punz
     */
    public function add_page() {
        add_options_page(
            __( 'NewsML-G2 Import Plugin Options', 'newsml-import' ),
            __( 'NewsML-G2 Import Plugin Options', 'newsml-import' ),
            'manage_options',
            'newsml-list-options',
            array( $this, 'options_do_page' )
        );
    }

    /**
     * Create the page for the options of the plugin.
     *
     * @author Bernhard Punz
     */
    public function options_do_page() {

        $options = get_option( $this->option_name );
        echo '
        <div class="wrap">
            <h2>' . __( 'NewsML-G2 Import Plugin Options', 'newsml-import' ) . '</h2>';

        echo '<ul>
        <li>' . __( '<strong>Step 1:</strong> Click the "Import Media Topics" button to import all mediatopics from the IPTC server.', 'newsml-import' ) . '</li>
        <li>' . __( '<strong>Step 2:</strong> Set the right address to your server', 'newsml-import' ) . '</li>
        <li>' . __( '<strong>Step 3:</strong> Set your login credentials for FTP if required.', 'newsml-import' ) . '</li>
        <li>' . __( '<strong>Step 4:</strong> Click the "Update newsposts" button to import all NewsML-G2 messages found in the denoted folder or the rss.xml file.', 'newsml-import' ) . '</li></ul>';

        echo '<form method="post" action="options.php">';
        settings_fields( 'newsml-list-options' );
        echo '<table class="form-table">
                    <tr valign="top">
                        <th scope="row">' . __( 'NewsML Folder URL (with trailing /):', 'newsml-import' ) . '</th>
                        <td>
                            <input type="text" name=" ' . $this->option_name . '[url_newsml]"
                                   value="' . $options['url_newsml'] . '" size="75">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'FTP Username (if necessary):', 'newsml-import' ) . '</th>
                        <td>
                            <input type="text" name="' . $this->option_name . '[ftp_user]"
                                   value="' . $options['ftp_user'] . '" size="75">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'FTP Password (if necessary):', 'newsml-import' ) . '</th>
                        <td>
                            <input type="password" name="' . $this->option_name . '[ftp_pass]"
                                   value="' . $options['ftp_pass'] . '" size="75">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Access via FTP:', 'newsml-import' ) . '</th>
                        <td>
                            <input type="checkbox"
                                   name="' . $this->option_name . '[enable_ftp]"
                                   value="yes" ' . checked( 'yes', $options['enable_ftp'], false ) . '>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Use rss.xml as source:', 'newsml-import' ) . '</th>
                        <td>
                            <input type="checkbox"
                                   name="' . $this->option_name . '[use_rss]"
                                   value="yes" ' . checked( 'yes', $options['use_rss'], false ) . '>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Delete posts after x days (0 = never):', 'newsml-import' ) . '</th>
                        <td>
                            <input type="number" name="' . $this->option_name . '[expire_time]"
                                   value="' . $options['expire_time'] . '" min="0" size="5">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Show news on front page:', 'newsml-import' ) . '</th>
                        <td>
                            <input type="checkbox"
                                   name="' . $this->option_name . '[news_frontpage]"
                                   value="yes" ' . checked( 'yes', $options['news_frontpage'], false ) . '>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Number of news in kiosk mode:', 'newsml-import' ) . '</th>
                        <td>
                               <input type="number" name="' . $this->option_name . '[kiosk_count]"
                               value="' . $options['kiosk_count'] . '" min="0" size="5">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Kiosk mode display duration in seconds:', 'newsml-import' ) . '</th>
                        <td>
                               <input type="number" name="' . $this->option_name . '[kiosk_duration]"
                               value="' . $options['kiosk_duration'] . '" min="0" size="5">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Import Media Topics:', 'newsml-import' ) . '</th>
                        <td>
                            <a class="button-primary"
                               href="?page=newsml-list-options&import-mediatopics=true">' . __( 'Import Media Topics', 'newsml-import' ) . '</a>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Update newsposts:', 'newsml-import' ) . '</th>
                        <td>
                            <a class="button-primary"
                               href="?page=newsml-list-options&update-posts=true">' . __( 'Update newsposts', 'newsml-import' ) . '</a>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">' . __( 'Check delete newsposts:', 'newsml-import' ) . '</th>
                        <td>
                            <a class="button-primary"
                               href="?page=newsml-list-options&check-delete-posts=true">' . __( 'Delete expired newsposts', 'newsml-import' ) . '</a>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="' . __( 'Save Changes', 'newsml-import' ) . '">
                </p>
            </form>
        </div>
    ';
    }

    /**
     * On activation of the plugin do some things.<br>
     * That includes setting the initial values for the news import (url, etc.).<br>
     * Registering the taxonomy for the mediatopics and the post type for the news.<br>
     * Inserting the mediatopics and updating the parents to the correct value.
     *
     * @author Bernhard Punz
     */
    public function activate() {

        // Update some option fields for the plugin
        update_option( $this->option_name, $this->data );

        add_option( 'newsmlimport_do_activation_redirect', true );

        $result = get_option( $this->option_name );

        if ( ! file_exists( $this->_home_path . $result['image_dir'] ) ) {
            mkdir( $this->_home_path . $result['image_dir'], 0777 );
        }

        // Register the taxonomny newsml_mediatopics
        $this->register_taxonomy_mediatopics();

        // Register the custom post type newsml_post
        $this->register_posttype_newsmlpost();
    }

    /**
     * Register the cronjob for the automatical updates
     *
     * @author Bernhard Punz
     */
    public function register_cron_stuff() {
        if ( ! wp_next_scheduled( 'newsml_update_delete_news_cron' ) ) {
            wp_schedule_event( time(), 'five_minutes', 'newsml_update_delete_news_cron' );
        }
    }

    /**
     * Add the new 5 minutes interval to
     *
     * @author Bernhard Punz
     *
     * @param array $array The array with the new interval
     * @return array The array with the new interval
     */
    public function add_five_minutes_interval( $array ) {
        $period = 300;
        $array['five_minutes'] = array(
            'interval' => $period,
            'display' => __( 'Every 5 minutes', 'newsml-import' ),
        );
        return $array;
    }

    /**
     * Checks if $haystack starts with $needle and returns the result
     *
     * @author Bernhard Punz
     *
     * @param string $haystack
     * @param string $needle
     * @return mixed
     */
    public function starts_with( $haystack, $needle ) {
        return $needle === "" || strrpos( $haystack, $needle, -strlen( $haystack ) ) !== FALSE;
    }

    /**
     * Registers the custom post type "newsml_post"
     *
     * @author Bernhard Punz
     */
    public function register_posttype_newsmlpost() {
        register_post_type( 'newsml_post',
            array(
                'labels' => array(
                    'name' => __( 'NewsML Posts', 'newsml-import' ),
                    'singular_name' => __( 'NewsML Post', 'newsml-import' ),
                    'add_new' => __( 'Add New Post', 'newsml-import' ),
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array( 'slug' => 'news' ),
            )
        );
    }

    /**
     * Adds the metafields for the mediatopic taxonomy to the "add mediatopic" page.
     *
     * @author Bernhard Punz
     */
    public function newsml_mediatopic_taxonomy_add_new_meta_field() {
        echo '
        <div class="form-field">
            <label for="term_meta[qcode]">' . _e( 'QCode', 'newsml-import' ) . '</label>
            <input type="text" name="term_meta[qcode]" id="term_meta[qcode]" value="">
            <p class="description">' . _e( 'Enter a value for this field', 'newsml-import' ) . '</p>
        </div>

        <div class="form-field">
            <label for="term_meta[modified]">' . _e( 'Last modified', 'newsml-import' ) . '</label>
            <input type="text" name="term_meta[modified]" id="term_meta[v]" value = "" >
            <p class="description"> ' . _e( 'Enter a value for this field', 'newsml-import' ) . '</p>
        </div>
        ';
    }

    /**
     * Adds the metafields for the mediatopic taxonomy to the "edit mediatopic" page.
     *
     * @author Bernhard Punz
     *
     * @param mixed $term The term to edit.
     */
    public function newsml_mediatopic_taxonomy_edit_meta_field( $term ) {

        $t_id = $term->term_id;

        $term_meta = get_option( "taxonomy_meta_$t_id" );
        echo '
        <tr class="form-field">
	    <th scope="row" valign="top"><label for="term_meta[qcode]">' . _e( 'QCode', 'newsml-import' ) . '</label></th>
	    	<td>
	    		<input type="text" name="term_meta[qcode]" id="term_meta[qcode]" value="' . ( esc_attr( $term_meta['qcode'] ) ? esc_attr( $term_meta['qcode'] ) : '' ) . '">
	    		<p class="description">' . _e( 'Enter a value for this field', 'newsml-import' ) . '</p>
	    	</td>
    	</tr>

    	<tr class="form-field">
	    <th scope="row" valign="top" ><label for="term_meta[modified]" >' . _e( 'Last modified', 'newsml-import' ) . '</label></th>
	    	<td>
	    		<input type="text" name="term_meta[modified]" id="term_meta[modified]" value="' . ( esc_attr( $term_meta['modified'] ) ? esc_attr( $term_meta['modified'] ) : '' ) . '">
	    		<p class="description">' . _e( 'Enter a value for this field', 'newsml-import' ) . '</p>
	    	</td>
    	</tr>
    	';
    }

    /**
     * Saves the metafields for the term when added manually.
     *
     * @author Bernhard Punz
     *
     * @param int $term_id The ID of the term to edit.
     */
    public function save_newsml_mediatopic_custom_meta( $term_id ) {

        if ( isset( $_POST['term_meta'] ) ) {

            $t_id = $term_id;
            $term_meta = get_option( "taxonomy_meta_" . $t_id );
            $cat_keys = array_keys( $_POST['term_meta'] );
            foreach ( $cat_keys as $key ) {
                if ( isset ( $_POST['term_meta'][$key] ) ) {
                    $term_meta[sanitize_text_field( $key )] = sanitize_text_field( $_POST['term_meta'][$key] );
                }
            }

            $term_meta = sanitize_term( $term_meta, 'newsml_mediatopic' );

            // Save the option array.
            update_option( "taxonomy_meta_" . $t_id, $term_meta );
        }
    }

    /**
     * Registers the taxonomy mediatopics.
     *
     * @author Bernhard Punz
     */
    public function register_taxonomy_mediatopics() {

        $labels = array(
            'name' => _x( 'Media Topic', 'Media Topics', 'newsml-import' ),
            'singular_name' => _x( 'Media Topic', 'Media Topics', 'newsml-import' ),
            'menu_name' => __( 'Media Topic', 'newsml-import' ),
            'all_items' => __( 'All Media Topics', 'newsml-import' ),
            'parent_item' => __( 'Parent Media Topic', 'newsml-import' ),
            'parent_item_colon' => __( 'Parent Item:', 'newsml-import' ),
            'new_item_name' => __( 'New Media Topic', 'newsml-import' ),
            'add_new_item' => __( 'Add Media Topic', 'newsml-import' ),
            'edit_item' => __( 'Edit Media Topic', 'newsml-import' ),
            'update_item' => __( 'Update Media Topic', 'newsml-import' ),
            'separate_items_with_commas' => __( 'Separate Media Topic with commas', 'newsml-import' ),
            'search_items' => __( 'Search Media Topics', 'newsml-import' ),
            'add_or_remove_items' => __( 'Add or remove Media Topics', 'newsml-import' ),
            'choose_from_most_used' => __( 'Choose from the most used Media Topics', 'newsml-import' ),
            'not_found' => __( 'Not Found', 'newsml-import' ),
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'rewrite' => array( 'slug' => 'mediatopic' ),
        );

        register_taxonomy( 'newsml_mediatopic', array( 'newsml_post' ), $args );
    }

    /**
     * Inserts all media topics from the parsed XML file from the IPTC.
     *
     * @author Bernhard Punz
     */
    public function insert_mediatopics() {

        // Get install language
        $wp_language = substr( get_locale(), 0, 2 );

        $topic_parser = new Mediatopic_Parser();

        $languages = array( 'en', 'de', 'fr', 'es', 'ar' );

        // Check language with array
        if ( in_array( $wp_language, $languages ) ) {
            $res_lang = $topic_parser->load( $wp_language );
        } else {
            // Fallback to en if we use a language that is not supported by the IPTC
            $res_lang = $topic_parser->load( 'en' );
        }

        // Foreach insert taxnonomy
        foreach ( $res_lang as $topic ) {

            $args = array(
                'description' => $topic->get_definition(),
            );

            $ins_res = wp_insert_term( $topic->get_name(), 'newsml_mediatopic', $args );

            if ( is_wp_error( $ins_res ) ) {
                // We assume that the error is that there is already a entry with the same slug
                // It's just a fallback for 2 media topics
                $args = array(
                    'description' => $topic->get_definition(),
                    'slug' => sanitize_title( $topic->get_name() . '-' . mt_rand() ),
                );
                $ins_res = wp_insert_term( $topic->get_name(), 'newsml_mediatopic', $args );
            }

            $_POST['term_meta']['qcode'] = sanitize_text_field( $topic->get_qcode() );
            $_POST['term_meta']['modified'] = sanitize_text_field( $topic->get_modified() );

            if ( count( $topic->get_broaders() ) > 0 ) {
                $_POST['term_meta']['broader'] = sanitize_text_field( $topic->get_broaders()[0] );
            }

            $this->save_newsml_mediatopic_custom_meta( $ins_res['term_taxonomy_id'] );
        }
    }

    /**
     * After the media topics got inserted as flat hierarchy, this function builds a tree.
     * Fetches all parents from the database and associates them with their children.
     *
     * @author Bernhard Punz
     */
    public function update_parents() {

        global $wpdb;

        // First we get all terms with the taxonomy newsml_mediatopic
        $taxonomies = get_terms( 'newsml_mediatopic', array( 'hide_empty' => 0 ) );

        foreach ( $taxonomies as $tax ) {

            // Get the additional data for this term from the options table
            $opts = get_option( 'taxonomy_meta_' . $tax->term_taxonomy_id );

            // Does this have a broader topic?
            if ( @$opts['broader'] != '' ) {

                // Get the options of the parent mediatopic so we can get the ID
                $res_parent = $wpdb->get_row( "SELECT option_name, option_value FROM  $wpdb->options
                WHERE option_value LIKE '%s:5:\"qcode\";s:15:\"" . @$opts['broader'] . "%'", ARRAY_A );

                // Get the ID of the parent mediatopic that is saved in term_taxonomy
                $splitted_taxonomy_meta = explode( '_', $res_parent['option_name'] );

                // Get the acutal parent term
                $parent_term = get_term( @$splitted_taxonomy_meta[2], 'newsml_mediatopic', ARRAY_A );

                // Update the child mediatopic, now with the correct parent ID
                $wpdb->update( $wpdb->term_taxonomy,
                    array( 'parent' => $parent_term['term_id'] ),
                    array( 'term_taxonomy_id' => $tax->term_taxonomy_id ) );
            }
        }
    }

    /**
     * Removes the WP cron that updates the posts and deletes all settings.
     *
     * @author Bernhard Punz
     */
    public function deactivate() {

        wp_clear_scheduled_hook( 'newsml_update_delete_news_cron' );

        delete_option( $this->option_name );
    }

    /**
     * Initializes the metaboxes for the newsml_post.
     *
     * @author Bernhard Punz
     */
    public function newsmlpost_meta_init() {
        add_meta_box(
            'newsmlpost_subtitle',
            __( 'Subtitle', 'newsml-import' ),
            array( $this, 'newsmlpost_subtitle_box_callback' ),
            'newsml_post'
        );

        add_meta_box(
            'newsmlpost_location',
            __( 'Locations', 'newsml-import' ),
            array( $this, 'newsmlpost_location_box_callback' ),
            'newsml_post'
        );
    }

    /**
     * Renders the subtitle box for the newsml_post to the add/edit page.
     *
     * @author Bernhard Punz
     *
     * @param mixed $post The post whose metadata is to load.
     */
    public function newsmlpost_subtitle_box_callback( $post ) {

        wp_nonce_field( 'newsmlpost_meta_box', 'newsmlpost_meta_box_nonce' );

        $value = get_post_meta( $post->ID, 'newsml_meta_subtitle', true );

        echo '<label for="newsmlpost_subtitle">';
        _e( 'Subtitle', 'newsml-import' );
        echo '</label> ';
        echo '<input type="text" id="newsmlpost_subtitle" name="newsmlpost_subtitle" value="' . esc_attr( $value ) . '" size="25" />';
    }

    /**
     * Renders the locations box for the newsml_post to the add/edit page.
     *
     * @author Bernhard Punz
     *
     * @param mixed $post The post whose metadata is to load.
     */
    public function newsmlpost_location_box_callback( $post ) {

        wp_nonce_field( 'newsmlpost_meta_box', 'newsmlpost_meta_box_nonce' );

        $value = get_post_meta( $post->ID, 'newsml_meta_location', true );

        echo '<label for="newsmlpost_location">';
        _e( 'Locations', 'newsml-import' );
        echo '</label> ';
        echo '<input type="text" id="newsmlpost_location" name="newsmlpost_location" value="' . esc_attr( $value ) . '" size="25" />';
    }

    /**
     * Saves the changes of both (subtitle and location) metaboxes to the database (add/edit).
     *
     * @author Bernhard Punz
     *
     * @param int $post_id The ID of the post whose metadata is to be saved.
     * @return mixed Returns the $post_id if not successful.
     */
    public function save_newsmlpost_meta( $post_id ) {

        if ( ! isset( $_POST['newsmlpost_meta_box_nonce'] ) ) {
            return $post_id;
        }

        $nonce = sanitize_text_field( $_POST['newsmlpost_meta_box_nonce'] );

        if ( ! wp_verify_nonce( $nonce, 'newsmlpost_meta_box' ) ) {
            return $post_id;
        }


        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Check the user's permissions.
        if ( 'newsml_post' == sanitize_text_field( $_POST['post_type'] ) ) {

            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;

        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        // Sanitize the user input.
        $sanitized_subtitle = sanitize_text_field( $_POST['newsmlpost_subtitle'] );
        $sanitized_locations = sanitize_text_field( $_POST['newsmlpost_location'] );

        // Update the meta field.
        update_post_meta( $post_id, 'newsml_meta_subtitle', $sanitized_subtitle );
        update_post_meta( $post_id, 'newsml_meta_location', $sanitized_locations );
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

/*
 * Inizialize the plugin.
 */
$NewsMLG2_Plugin = new NewsMLG2_Importer_Plugin();

