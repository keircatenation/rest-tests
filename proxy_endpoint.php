<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
#[AllowDynamicProperties]
class Smartsheet_Controller {
    public $column_keys = array();
    public $program_import_kind = '';
    public $reg_end_regex = '/(^[0-9]+)\s(days|hours|minutes)/i';
    public $member_discount_regex = '/(^[0-9]+)/i';

    public function __construct(){
        $this->namespace = 'rest-tests/v1';
        $this->resource_name = 'smartsheet';
        $this->has_api_key = get_option( 'smartsheet_api_key' ) ? true : false;
        $this->smartsheet_authorization = 'Bearer ' . get_option( 'smartsheet_api_key' );
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_results' ),
                'permission_callback' => '__return_true',
                'arguments' => array(
                    'id' => array(
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    )
                ),
                'sanitize_callback' => function ( $value, $request, $param ) {
                    return sanitize_text_field( $value );
                }
            )
        ) );
    }

    // gets the Results from Smartsheet
    public function get_results( $request ) {
        //If there's no API key in admin, then sned error that we can't make a call
        if ( !$this->has_api_key ) {
            return new WP_Error( 'no_smartsheet_key', __( 'Please enter a Smartsheet API access key into the admin settings.', 'mag-extension' ), array( 'status' => 404 ) );
        }

        $url = 'https://api.smartsheet.com/2.0/sheets/' . $request['id'];
        $get_response = wp_remote_get( $url, array(
            'method' => 'GET',
            'headers' => array(
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "Authorization" => $this->smartsheet_authorization
            )
        ) );
        $json = json_decode( $get_response['body'], true );
        
        // If we get an error from Smartsheet (sheet not found, or no authorization), send error back
        if ( isset( $json['errorCode'] ) ) {
            return new WP_Error( 'smartsheet_error', __( $json['message'], 'mag-extension' ), array( 'status' => 404 ) );
        }

        $this->column_keys = $this->create_column_keys( $json['columns'] );
        $filter = $this->filter_results( $json['rows'] );
        $results = $this->map_results( $filter );

        $response = new WP_REST_Response( array_values( $results ) );
        $response->set_status(200);

        return $response;
    }

    /**
     * Creating an array of indexes mapped onto what the columns are named, so we can actually access the correct information without worrying about accessing the wrong column because the sheet has changed
     */
    public function create_column_keys( $columns ) {
        /**
         * Setting the kind of program import based on what the column title is; this sets some features later on
         */

        if ( array_search( 'Class Name', array_column( $columns, 'title' ) ) ) {
            $name_index = array_search( 'Class Name', array_column( $columns, 'title' ) );
            $this->program_import_kind = 'class';
            $camp_age_filter = false;
        } else if(  array_search( 'Camp Name', array_column( $columns, 'title' ) ) ) {
            $name_index = array_search( 'Camp Name', array_column( $columns, 'title' ) );
            $this->program_import_kind = 'camp';
        } else {
            $this->program_import_kind = 'nothing';
            $name_index = false;
        }

        switch( $this->program_import_kind ){
            case 'class':
                // age filters need to be age filters; 
                $audience_index = array_search( 'Age Filters', array_column( $columns, 'title' ) );
                $medium_index = array_search( 'Medium Filters', array_column( $columns, 'title' ) );
                $camp_age_filter = false;
                break;
            case 'camp':
                $camp_age_filter = array_search( 'Age Filters', array_column( $columns, 'title' ) );
                $audience_index = false;
                $medium_index = false;
                break;
            default:
                $audience_index = false;
                $medium_index = false;
                $camp_age_filter = false;
                break;
        }

        $description_index = ( $this->program_import_kind == 'class' ) ? array_search( 'Class Description', array_column( $columns, 'title' ) ) : array_search( 'Description', array_column( $columns, 'title' ) );

        return array(
            'cancelled'     =>      array_search( 'Cancelled', array_column( $columns, 'title' ) ),
            'capacity'      =>      array_search( 'Available Capacity', array_column( $columns, 'title' ) ),
            'approved'      =>      array_search( 'Approved for WEB', array_column( $columns, 'title' ) ),
            'code'          =>      array_search( 'Code', array_column( $columns, 'title' ) ),
            'name'          =>      $name_index,
            'weekday'       =>      array_search( 'Day of the week', array_column( $columns, 'title' ) ),
            'num_weeks'     =>      array_search( 'Number of weeks', array_column( $columns, 'title' ) ),
            'start_time'    =>      array_search( 'Start Time', array_column( $columns, 'title' ) ),
            'end_time'      =>      array_search( 'End Time', array_column( $columns, 'title' ) ),
            'start_date'    =>      array_search( 'Start Date', array_column( $columns, 'title' ) ),
            'end_date'      =>      array_search( 'End Date', array_column( $columns, 'title' ) ),
            'exception'     =>      array_search( 'Date Exception', array_column( $columns, 'title' ) ),
            'teacher'       =>      array_search( 'Teacher', array_column( $columns, 'title' ) ),
            'age_range'     =>      array_search( 'Age Range', array_column( $columns, 'title' ) ),
            'price'         =>      array_search( 'Price', array_column( $columns, 'title' ) ),
            'description'   =>      $description_index,
            'altru_link'    =>      array_search( 'Altru Link for Registration', array_column( $columns, 'title' ) ),
            'image_ID'      =>      array_search( 'Cover Image ID', array_column( $columns, 'title' ) ),
            'mat_provided'  =>      array_search( 'All Materials Provided', array_column( $columns, 'title' ) ),
            'mat_list_link' =>      array_search( 'Materials List Link', array_column( $columns, 'title' ) ),
            'mem_reg'       =>      array_search( 'Member Registration Start', array_column( $columns, 'title' ) ),
            'gen_reg'       =>      array_search( 'General Registration Start', array_column( $columns, 'title' ) ),
            'reg_end'       =>      array_search( 'On-Sale Period End', array_column( $columns, 'title' ) ),
            'inst_approval' =>      array_search( 'Needs Instructor Approval to Register', array_column( $columns, 'title' ) ),
            'member_discount' =>    array_search( 'Member Discount', array_column( $columns, 'title' ) ),
            'camp_age_filter' =>    $camp_age_filter,
            'audience'      =>      $audience_index,
            'medium'        =>      $medium_index
        );
    }

    /**
     * Filtering results so we're not worrying about programs that aren't approved, or that don't have certain information
     */
    public function filter_results( $results ) {
        return array_values( array_filter( $results, function( $row ) {
            $approved = isset( $row['cells'][ $this->column_keys['approved'] ]['value'] ) ? $row['cells'][ $this->column_keys['approved'] ]['value'] : false;
            return (
                isset( $row['cells'][ $this->column_keys['name'] ]['value'] )
                && isset( $row['cells'][ $this->column_keys['start_date'] ]['value'] )
                && isset( $row['cells'][ $this->column_keys['start_time'] ]['displayValue'] )
                && isset( $row['cells'][ $this->column_keys['end_date'] ]['value'] )
                && isset( $row['cells'][ $this->column_keys['end_time'] ]['displayValue'] )
                && isset( $row['cells'][ $this->column_keys['price'] ]['value'] )
                && $approved
            );
        } ) );
    }

    /**
     * Mapping results into what we want to return
     */
    public function map_results( $results ) {
        return array_map( function ( $row ) {
            /**
             * $start: used to make the Event Start Date, Event Start Time, and Description
             * $end: used to make the Event End Date and Event End Time
             * $end_date: used to make the Description
             * $reg_end_interval
             * $reg_end
             */
            $start = new DateTime( $row['cells'][ $this->column_keys['start_date'] ]['value'] . $row['cells'][ $this->column_keys['start_time'] ]['displayValue'] );
            $end = new DateTime( $row['cells'][ $this->column_keys['start_date'] ]['value'] . $row['cells'][ $this->column_keys['end_time'] ]['displayValue'] );
            $end_date = new DateTime( $row['cells'][ $this->column_keys['end_date'] ]['value'] . $row['cells'][ $this->column_keys['end_time'] ]['displayValue'] );

            $reg_end_values = array();
            if ( $this->column_keys['reg_end'] && isset( $row['cells'][ $this->column_keys['reg_end'] ]['value'] ) ) {
                preg_match( $this->reg_end_regex, $row['cells'][ $this->column_keys['reg_end'] ]['value'], $reg_end_values );
            }

            $reg_end_interval = !empty( $reg_end_values ) ? DateInterval::createFromDateString( $reg_end_values[0] ) : null;
            if ( $reg_end_interval ) {
                $reg_end = new DateTime( $row['cells'][ $this->column_keys['start_date'] ]['value'] . $row['cells'][ $this->column_keys['start_time'] ]['displayValue'] );
                $reg_end->sub( $reg_end_interval );
            } else {
                $reg_end = $start;
            }

            $mem_reg = ( $this->column_keys['mem_reg'] && isset( $row['cells'][ $this->column_keys['mem_reg'] ]['value'] ) ) ? new DateTime( $row['cells'][ $this->column_keys['mem_reg'] ]['value'] . '10:00' ) : null;
            $gen_reg = ( $this->column_keys['gen_reg'] && isset( $row['cells'][ $this->column_keys['gen_reg'] ]['value'] ) ) ? new DateTime( $row['cells'][ $this->column_keys['gen_reg'] ]['value'] . '10:00' ) : null;

            if ( $mem_reg ) {
                $reg_start = $mem_reg->format( 'Y-m-d H:i' );
            } else if ( $gen_reg ) {
                $reg_start = $gen_reg->format( 'Y-m-d H:i' );
            } else {
                $reg_start = '';
            }

            $image = isset( $row['cells'][ $this->column_keys['image_ID'] ]['value'] ) ? wp_get_attachment_image_src( (int)$row['cells'][ $this->column_keys['image_ID'] ]['value'], 'full' ) : false;

            // Importing Event Status for historical events
            $status = '';
            if ( isset( $row['cells'][ $this->column_keys['cancelled'] ]['value'] ) && (bool)$row['cells'][ $this->column_keys['cancelled'] ]['value'] ) {
                $status = 'canceled';
            } else if( isset( $row['cells'][ $this->column_keys['capacity'] ]['value'] ) && $row['cells'][ $this->column_keys['capacity'] ]['value'] == 0 ) {
                $status = 'soldout';
            }

            // Putting together the price description with member discount
            $price_description = '';
            $event_cost = (int)$row['cells'][ $this->column_keys['price'] ]['value'];

            $member_discount = ( $this->column_keys['member_discount'] && isset( $row['cells'][ $this->column_keys['member_discount'] ]['value'] ) ) ? $row['cells'][ $this->column_keys['member_discount'] ]['value'] : null;

            if ( $member_discount ) {
                $mem_discount_values = array();
                preg_match( $this->member_discount_regex, $row['cells'][ $this->column_keys['member_discount'] ]['value'], $mem_discount_values );
                if ( !empty( $mem_discount_values ) ) {
                    $ec = '$' . $event_cost;
                    $price_description = "{$ec}; {$mem_discount_values[0]}% discount for MAG Members";
                }
            }

            /**
             * create the name with the code
             */
            $name = $row['cells'][ $this->column_keys['name'] ]['value'];
            $code = isset( $row['cells'][ $this->column_keys['code'] ]['displayValue'] ) ? $row['cells'][ $this->column_keys['code'] ]['displayValue'] : '';
            if ( $code ) $name .= sprintf( ' (%s)', $code );

            $camp_age_filter = isset( $row['cells'][ $this->column_keys['camp_age_filter'] ]['displayValue'] ) ? $row['cells'][ $this->column_keys['camp_age_filter'] ]['displayValue'] : '';

            // Audience
            if ( $this->program_import_kind == 'camp' ) {
                if ( $camp_age_filter == 'TEEN' ) {
                    $audience = 'For Teens';
                    $audience_tax = 'For Teens';
                } else {
                    $audience = 'For Kids';
                    $audience_tax = 'For Kids';
                }
            } else {
                $aud_array = ( $this->column_keys['audience'] && isset( $row['cells'][ $this->column_keys['audience'] ]['displayValue'] ) ) ? explode( ', ',  $row['cells'][ $this->column_keys['audience'] ]['displayValue']) : array();
                $audience = implode( '|', $aud_array );
                $audience_tax = implode( ', ', $aud_array );
            }

            // Class Medium
            $med_array = ( $this->column_keys['medium'] && isset( $row['cells'][ $this->column_keys['medium'] ]['displayValue'] ) ) ? explode( ', ',  $row['cells'][ $this->column_keys['medium'] ]['displayValue']) : array();
            $class_medium = implode( '|', $med_array );
            $class_medium_tax = implode( ', ', $med_array );

            /**
             * creating the description
             */
            $description = '';
            if( isset( $row['cells'][ $this->column_keys['description'] ]['displayValue'] ) ) {
                $italics = preg_replace( '/(\*{1})(.[^*]+)(\*{1})/i', '<em>\2</em>', $row['cells'][ $this->column_keys['description'] ]['displayValue'] );
                $paragraphs = preg_replace( '/\s*\|\|\s*/i', '</p><p>', $italics );
                $description .= sprintf( '<p>%s</p>', $paragraphs );
            }
            
            return array(
                'smartsheet_id'     => $row['id'],
                'event_name'        => wp_kses_post( $name ),
                'event_description' => wp_kses_post( $description ),
                'start_date'        => $start->format( 'Y-m-d' ),
                'start_time'        => $start->format( 'g:i a' ),
                'end_date'          => $end->format( 'Y-m-d' ),
                'end_time'          => $end->format( 'g:i a' ),
                'reg_end'           => $reg_end->format( 'Y-m-d H:i' ),
                'event_cost'        => $event_cost,
                'price_description' => $price_description,
                'location'          => 'Creative Workshop',
                'audience'          => $audience,
                'medium'            => $class_medium,
                'audience_tax'      => $audience_tax,
                'medium_tax'        => $class_medium_tax,
                'featured_image'    => is_array( $image ) ? $image[0] : '',
                'status'            => esc_html( $status ),
                'event_organizer'   => 'Creative Workshop',
                'timezone'          => 'America/New_York'
            );
        }, $results );
    }
}