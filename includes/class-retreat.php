<?php
/**
 * Retreat entity class
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retreat entity class
 */
class DFX_PRL_Retreat {

    /**
     * Retreat ID
     *
     * @var int
     */
    public $id;

    /**
     * Retreat name
     *
     * @var string
     */
    public $name;

    /**
     * Retreat location
     *
     * @var string
     */
    public $location;

    /**
     * Retreat start date
     *
     * @var string
     */
    public $start_date;

    /**
     * Retreat end date
     *
     * @var string
     */
    public $end_date;

    /**
     * Created date
     *
     * @var string
     */
    public $created_at;

    /**
     * Updated date
     *
     * @var string
     */
    public $updated_at;

    /**
     * Constructor
     *
     * @param array $data Retreat data
     */
    public function __construct( $data = array() ) {
        if ( ! empty( $data ) ) {
            $this->populate( $data );
        }
    }

    /**
     * Populate retreat data
     *
     * @param array $data Retreat data
     */
    public function populate( $data ) {
        $this->id         = isset( $data['id'] ) ? intval( $data['id'] ) : 0;
        $this->name       = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $this->location   = isset( $data['location'] ) ? sanitize_text_field( $data['location'] ) : '';
        $this->start_date = isset( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : '';
        $this->end_date   = isset( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : '';
        $this->created_at = isset( $data['created_at'] ) ? $data['created_at'] : '';
        $this->updated_at = isset( $data['updated_at'] ) ? $data['updated_at'] : '';
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'         => $this->id,
            'name'       => $this->name,
            'location'   => $this->location,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        );
    }

    /**
     * Validate retreat data
     *
     * @return array Array of validation errors
     */
    public function validate() {
        $errors = array();

        // Validate name
        if ( empty( $this->name ) ) {
            $errors[] = __( 'Retreat name is required.', 'dfx-parish-retreat-letters' );
        }

        // Validate location
        if ( empty( $this->location ) ) {
            $errors[] = __( 'Retreat location is required.', 'dfx-parish-retreat-letters' );
        }

        // Validate start date
        if ( empty( $this->start_date ) ) {
            $errors[] = __( 'Start date is required.', 'dfx-parish-retreat-letters' );
        } elseif ( ! $this->is_valid_date( $this->start_date ) ) {
            $errors[] = __( 'Start date is not valid.', 'dfx-parish-retreat-letters' );
        }

        // Validate end date
        if ( empty( $this->end_date ) ) {
            $errors[] = __( 'End date is required.', 'dfx-parish-retreat-letters' );
        } elseif ( ! $this->is_valid_date( $this->end_date ) ) {
            $errors[] = __( 'End date is not valid.', 'dfx-parish-retreat-letters' );
        }

        // Validate date range
        if ( ! empty( $this->start_date ) && ! empty( $this->end_date ) && 
             $this->is_valid_date( $this->start_date ) && $this->is_valid_date( $this->end_date ) ) {
            if ( strtotime( $this->start_date ) > strtotime( $this->end_date ) ) {
                $errors[] = __( 'Start date cannot be after end date.', 'dfx-parish-retreat-letters' );
            }
        }

        return $errors;
    }

    /**
     * Check if date is valid
     *
     * @param string $date Date string
     * @return bool
     */
    private function is_valid_date( $date ) {
        $d = DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }

    /**
     * Get formatted start date
     *
     * @param string $format Date format
     * @return string
     */
    public function get_formatted_start_date( $format = 'F j, Y' ) {
        if ( empty( $this->start_date ) ) {
            return '';
        }
        return date_i18n( $format, strtotime( $this->start_date ) );
    }

    /**
     * Get formatted end date
     *
     * @param string $format Date format
     * @return string
     */
    public function get_formatted_end_date( $format = 'F j, Y' ) {
        if ( empty( $this->end_date ) ) {
            return '';
        }
        return date_i18n( $format, strtotime( $this->end_date ) );
    }

    /**
     * Get duration in days
     *
     * @return int
     */
    public function get_duration_days() {
        if ( empty( $this->start_date ) || empty( $this->end_date ) ) {
            return 0;
        }
        
        $start = new DateTime( $this->start_date );
        $end = new DateTime( $this->end_date );
        $diff = $start->diff( $end );
        
        return $diff->days + 1; // Include both start and end days
    }
}