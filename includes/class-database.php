<?php
/**
 * Database operations class
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database class for retreat operations
 */
class DFX_PRL_Database {

    /**
     * Table name
     *
     * @var string
     */
    private $table_name;

    /**
     * WordPress database object
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'dfx_prl_retreats';
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            location varchar(255) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_name (name),
            KEY idx_location (location),
            KEY idx_start_date (start_date),
            KEY idx_end_date (end_date)
        ) {$charset_collate};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Drop database tables
     */
    public function drop_tables() {
        $sql = "DROP TABLE IF EXISTS {$this->table_name}";
        $this->wpdb->query( $sql );
    }

    /**
     * Get all retreats
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_retreats( $args = array() ) {
        $defaults = array(
            'orderby' => 'start_date',
            'order'   => 'DESC',
            'limit'   => 20,
            'offset'  => 0,
            'search'  => '',
            'start_date_from' => '',
            'start_date_to'   => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array( '1=1' );
        $where_values = array();

        // Search functionality
        if ( ! empty( $args['search'] ) ) {
            $where_clauses[] = '(name LIKE %s OR location LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Date range filtering
        if ( ! empty( $args['start_date_from'] ) ) {
            $where_clauses[] = 'start_date >= %s';
            $where_values[] = $args['start_date_from'];
        }

        if ( ! empty( $args['start_date_to'] ) ) {
            $where_clauses[] = 'start_date <= %s';
            $where_values[] = $args['start_date_to'];
        }

        $where_sql = implode( ' AND ', $where_clauses );

        // Build ORDER BY clause
        $allowed_orderby = array( 'id', 'name', 'location', 'start_date', 'end_date', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'start_date';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        // Build LIMIT clause
        $limit = intval( $args['limit'] );
        $offset = intval( $args['offset'] );

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";

        if ( ! empty( $where_values ) ) {
            $sql = $this->wpdb->prepare( $sql, $where_values );
        }

        $results = $this->wpdb->get_results( $sql, ARRAY_A );

        $retreats = array();
        foreach ( $results as $result ) {
            $retreats[] = new DFX_PRL_Retreat( $result );
        }

        return $retreats;
    }

    /**
     * Get total count of retreats
     *
     * @param array $args Query arguments
     * @return int
     */
    public function get_retreats_count( $args = array() ) {
        $where_clauses = array( '1=1' );
        $where_values = array();

        // Search functionality
        if ( ! empty( $args['search'] ) ) {
            $where_clauses[] = '(name LIKE %s OR location LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Date range filtering
        if ( ! empty( $args['start_date_from'] ) ) {
            $where_clauses[] = 'start_date >= %s';
            $where_values[] = $args['start_date_from'];
        }

        if ( ! empty( $args['start_date_to'] ) ) {
            $where_clauses[] = 'start_date <= %s';
            $where_values[] = $args['start_date_to'];
        }

        $where_sql = implode( ' AND ', $where_clauses );
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_sql}";

        if ( ! empty( $where_values ) ) {
            $sql = $this->wpdb->prepare( $sql, $where_values );
        }

        return (int) $this->wpdb->get_var( $sql );
    }

    /**
     * Get retreat by ID
     *
     * @param int $id Retreat ID
     * @return DFX_PRL_Retreat|null
     */
    public function get_retreat( $id ) {
        $sql = $this->wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id );
        $result = $this->wpdb->get_row( $sql, ARRAY_A );

        if ( $result ) {
            return new DFX_PRL_Retreat( $result );
        }

        return null;
    }

    /**
     * Insert new retreat
     *
     * @param DFX_PRL_Retreat $retreat Retreat object
     * @return int|false Retreat ID on success, false on failure
     */
    public function insert_retreat( $retreat ) {
        $data = array(
            'name'       => $retreat->name,
            'location'   => $retreat->location,
            'start_date' => $retreat->start_date,
            'end_date'   => $retreat->end_date,
        );

        $format = array( '%s', '%s', '%s', '%s' );

        $result = $this->wpdb->insert( $this->table_name, $data, $format );

        if ( $result !== false ) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update retreat
     *
     * @param DFX_PRL_Retreat $retreat Retreat object
     * @return bool
     */
    public function update_retreat( $retreat ) {
        $data = array(
            'name'       => $retreat->name,
            'location'   => $retreat->location,
            'start_date' => $retreat->start_date,
            'end_date'   => $retreat->end_date,
        );

        $where = array( 'id' => $retreat->id );
        $format = array( '%s', '%s', '%s', '%s' );
        $where_format = array( '%d' );

        $result = $this->wpdb->update( $this->table_name, $data, $where, $format, $where_format );

        return $result !== false;
    }

    /**
     * Delete retreat
     *
     * @param int $id Retreat ID
     * @return bool
     */
    public function delete_retreat( $id ) {
        $result = $this->wpdb->delete( 
            $this->table_name, 
            array( 'id' => $id ), 
            array( '%d' ) 
        );

        return $result !== false;
    }

    /**
     * Check if retreat exists
     *
     * @param int $id Retreat ID
     * @return bool
     */
    public function retreat_exists( $id ) {
        $sql = $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d", $id );
        return (int) $this->wpdb->get_var( $sql ) > 0;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }
}