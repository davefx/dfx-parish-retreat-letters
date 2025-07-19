<?php
/**
 * Admin functionality class
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class for retreat management
 */
class DFX_PRL_Admin {

    /**
     * Database instance
     *
     * @var DFX_PRL_Database
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new DFX_PRL_Database();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_dfx_prl_delete_retreat', array( $this, 'ajax_delete_retreat' ) );
        add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Retreats', 'dfx-parish-retreat-letters' ),
            __( 'Retreats', 'dfx-parish-retreat-letters' ),
            'manage_options',
            'dfx-prl-retreats',
            array( $this, 'retreats_page' ),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'dfx-prl-retreats',
            __( 'All Retreats', 'dfx-parish-retreat-letters' ),
            __( 'All Retreats', 'dfx-parish-retreat-letters' ),
            'manage_options',
            'dfx-prl-retreats',
            array( $this, 'retreats_page' )
        );

        add_submenu_page(
            'dfx-prl-retreats',
            __( 'Add New Retreat', 'dfx-parish-retreat-letters' ),
            __( 'Add New', 'dfx-parish-retreat-letters' ),
            'manage_options',
            'dfx-prl-add-retreat',
            array( $this, 'add_retreat_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on our plugin pages
        if ( strpos( $hook, 'dfx-prl' ) === false ) {
            return;
        }

        wp_enqueue_style( 
            'dfx-prl-admin', 
            DFX_PRL_PLUGIN_URL . 'admin/css/admin.css', 
            array(), 
            DFX_PRL_VERSION 
        );

        wp_enqueue_script( 
            'dfx-prl-admin', 
            DFX_PRL_PLUGIN_URL . 'admin/js/admin.js', 
            array( 'jquery' ), 
            DFX_PRL_VERSION, 
            true 
        );

        wp_localize_script( 'dfx-prl-admin', 'dfx_prl_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dfx_prl_nonce' ),
            'confirm_delete' => __( 'Are you sure you want to delete this retreat?', 'dfx-parish-retreat-letters' ),
        ));
    }

    /**
     * Retreats list page
     */
    public function retreats_page() {
        // Handle search and pagination
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page = 20;
        $offset = ( $paged - 1 ) * $per_page;

        // Date filters with validation
        $start_date_from = '';
        $start_date_to = '';
        
        if ( isset( $_GET['start_date_from'] ) && ! empty( $_GET['start_date_from'] ) ) {
            $date = sanitize_text_field( $_GET['start_date_from'] );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $start_date_from = $date;
            }
        }
        
        if ( isset( $_GET['start_date_to'] ) && ! empty( $_GET['start_date_to'] ) ) {
            $date = sanitize_text_field( $_GET['start_date_to'] );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $start_date_to = $date;
            }
        }

        $args = array(
            'search' => $search,
            'limit'  => $per_page,
            'offset' => $offset,
            'start_date_from' => $start_date_from,
            'start_date_to'   => $start_date_to,
        );

        $retreats = $this->database->get_retreats( $args );
        $total_retreats = $this->database->get_retreats_count( $args );
        $total_pages = ceil( $total_retreats / $per_page );

        include DFX_PRL_PLUGIN_DIR . 'admin/partials/retreats-list.php';
    }

    /**
     * Add retreat page
     */
    public function add_retreat_page() {
        $retreat = new DFX_PRL_Retreat();
        $edit_mode = false;

        // Handle edit mode
        if ( isset( $_GET['edit'] ) && ! empty( $_GET['edit'] ) ) {
            $retreat_id = intval( $_GET['edit'] );
            $existing_retreat = $this->database->get_retreat( $retreat_id );
            
            if ( $existing_retreat ) {
                $retreat = $existing_retreat;
                $edit_mode = true;
            } else {
                // Retreat not found, redirect with error
                $this->add_admin_notice( __( 'Retreat not found.', 'dfx-parish-retreat-letters' ), 'error' );
                wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
                exit;
            }
        }

        include DFX_PRL_PLUGIN_DIR . 'admin/partials/retreat-form.php';
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        // Handle retreat form submission
        if ( isset( $_POST['dfx_prl_submit_retreat'] ) && wp_verify_nonce( $_POST['dfx_prl_nonce'], 'dfx_prl_retreat_form' ) ) {
            $this->handle_retreat_form_submission();
        }
    }

    /**
     * Handle retreat form submission
     */
    private function handle_retreat_form_submission() {
        $retreat = new DFX_PRL_Retreat();
        
        // Get form data
        $retreat->name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $retreat->location = isset( $_POST['location'] ) ? sanitize_text_field( $_POST['location'] ) : '';
        $retreat->start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $retreat->end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';

        // Check if this is an edit
        $edit_mode = false;
        if ( isset( $_POST['retreat_id'] ) && ! empty( $_POST['retreat_id'] ) ) {
            $retreat->id = intval( $_POST['retreat_id'] );
            $edit_mode = true;
        }

        // Validate retreat data
        $errors = $retreat->validate();

        if ( empty( $errors ) ) {
            if ( $edit_mode ) {
                // Update existing retreat
                $result = $this->database->update_retreat( $retreat );
                if ( $result ) {
                    $this->add_admin_notice( __( 'Retreat updated successfully.', 'dfx-parish-retreat-letters' ), 'success' );
                    wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
                    exit;
                } else {
                    $this->add_admin_notice( __( 'Error updating retreat.', 'dfx-parish-retreat-letters' ), 'error' );
                }
            } else {
                // Create new retreat
                $result = $this->database->insert_retreat( $retreat );
                if ( $result ) {
                    $this->add_admin_notice( __( 'Retreat created successfully.', 'dfx-parish-retreat-letters' ), 'success' );
                    wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
                    exit;
                } else {
                    $this->add_admin_notice( __( 'Error creating retreat.', 'dfx-parish-retreat-letters' ), 'error' );
                }
            }
        } else {
            // Display validation errors
            foreach ( $errors as $error ) {
                $this->add_admin_notice( $error, 'error' );
            }
        }
    }

    /**
     * AJAX handler for deleting retreats
     */
    public function ajax_delete_retreat() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'dfx_prl_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions.', 'dfx-parish-retreat-letters' ) );
        }

        $retreat_id = intval( $_POST['retreat_id'] );

        if ( $this->database->delete_retreat( $retreat_id ) ) {
            wp_send_json_success( array(
                'message' => __( 'Retreat deleted successfully.', 'dfx-parish-retreat-letters' )
            ));
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error deleting retreat.', 'dfx-parish-retreat-letters' )
            ));
        }
    }

    /**
     * Add admin notice
     */
    private function add_admin_notice( $message, $type = 'info' ) {
        add_action( 'admin_notices', function() use ( $message, $type ) {
            printf( 
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr( $type ),
                esc_html( $message )
            );
        });
    }

    /**
     * Get page URL
     */
    public function get_page_url( $page, $args = array() ) {
        $url = admin_url( 'admin.php?page=' . $page );
        
        if ( ! empty( $args ) ) {
            $url = add_query_arg( $args, $url );
        }
        
        return $url;
    }
}