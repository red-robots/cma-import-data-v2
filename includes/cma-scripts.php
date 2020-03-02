<?php

/* Add setting to properties custom post type */
add_action('admin_menu', 'cma_import_submenu_page');
function cma_import_submenu_page() {
  add_submenu_page( 'edit.php?post_type=properties', 'CMA Import', 'Import', 'manage_options', 'properties-import-data', 'cma_import_submenu_page_callback' ); 
}

function cma_import_submenu_page_callback() {
    $data = ( isset($_POST) && $_POST ) ? $_POST : '';
    $import_type = ( isset($data['importtype']) && $data['importtype'] ) ? $data['importtype']:'add';
    $res = cma_search_import_data($data);
    $post_ids = ( isset($res['postids']) && $res['postids'] ) ? $res['postids']:'';
    $message = ( isset($res['message']) && $res['message'] ) ? $res['message']:'';
     $action = ( isset($res['action']) && $res['action'] ) ? $res['action'] : '';
    include('admin-view.php');
}

function cma_search_import_data($data) {
    $message = '';
    $duplicates = array();
    $itemsImported = array();
    $action = '';
    $import_type = ( isset($data['importtype']) && $data['importtype'] ) ? $data['importtype']:'add';
    $isSubmitted = ( isset($data['cma_search_submit_values']) ) ? true : false;
    $csvFile = ( isset($_FILES['cma_data_properties']) && $_FILES['cma_data_properties']['error']==0 ) ? $_FILES['cma_data_properties'] : '';

    if(!$isSubmitted) return false;

    if($csvFile) {
        $res = do_import_entries($csvFile,$data,$import_type);
        $action = ( isset($res['action']) && $res['action'] ) ? $res['action'] : '';
        $itemsImported = ( isset($res['items']) && $res['items'] ) ? $res['items'] : '';
        $duplicates = ( isset($res['duplicates']) && $res['duplicates'] ) ? $res['duplicates'] : '';
        $message = ( isset($res['message']) && $res['message'] ) ? $res['message'] : '';
    } else {
        $message = 'Please upload a CSV File.';
    }

    $response['message'] =  $message;
    $response['action'] = $action;
    $response['postids'] = '';

    if($itemsImported) {
        $response['postids'] = $itemsImported;
        $response['total'] = count($itemsImported);
    } 

    return $response;
}


/* ADD or UPDATE ENTRIES */
function do_import_entries($csvFileData,$data,$action) {
    $result['items'] = array();
    $result['duplicates'] = array();
    $result['action'] = $action;
    $result['message'] = 'Import Failed. Please try again.';
    $fieldnames = get_property_fieldnames();
    $userLoggedinID = get_current_user_id();

    if( $data && array_key_exists( 'cma_search_submit_values', $data) ){
        if( ! ( $csvFileData['error'] > UPLOAD_ERR_OK ) ){
            $mimes = array('application/vnd.ms-excel','text/csv');
            if( !in_array($csvFileData['type'], $mimes) ){
                $message = 'Invalid file type.';
            } else {
                $csv_to_array = array_map('str_getcsv', file($csvFileData['tmp_name']));
                $columnNames = ( isset($csv_to_array[0]) && $csv_to_array[0] ) ? $csv_to_array[0]:'';
                if($csv_to_array) {

                    $fieldNames = get_property_fieldnames(); /* Custom Fields */
                    $columns = array();

                    $i=0; foreach( $csv_to_array as $values) {
                        $latitude = '';
                        $longitude = '';
                        
                        if($i==0) { /* This is the Row 1 from Excel Sheet  */

                            foreach($values as $v) {
                                $fkey = preg_replace('/\s+/', '', $v); /* remove white space */
                                if( array_key_exists($fkey, $fieldNames) ) {
                                    $columns[] = $fkey;
                                }
                            }

                        } else {

                            $coordinates = array('latitude','longitude');
                            $newValues = array();
                            $args['post_author'] = $userLoggedinID;
                            $args['post_status'] = 'publish';
                            $args['post_type'] = 'properties';
                            $code = '';
                            $posttitle = '';
                            $field_name = '';
                            if($columns) {

                                /* Values for each row */
                                foreach($columns as $k=>$col) {
                                    $fieldVal = ($values[$k]) ? $values[$k] : '';
                                    $field_name = $col;
                                    
                                    if($col=='coupon_code') {
                                        $code = $fieldVal;
                                    } 
                                    if($col=='community_name') {
                                        $posttitle = $fieldVal;
                                        $args['post_title'] = $fieldVal;
                                    } 
                                }

                                if( $post_id = check_code_exists( $code ) ) {

                                    /* UPDATE ENTRIES */
                                    if( $action=='update' ) {

                                        $args['ID'] = $post_id;
                                        wp_update_post($args);

                                        /* Custom Fields */
                                        foreach($columns as $j=>$col) {
                                            $subFieldVal = ($values[$j]) ? $values[$j] : '';
                                            /* Google Map Coordinates */
                                            if( in_array($col, $coordinates) ) {
                                                $selector = 'gmapLatLong_'.$col;
                                                update_field( $selector, $subFieldVal, $post_id );
                                            } else {
                                                update_field( $col, $subFieldVal, $post_id );
                                            }
                                        }
                                        $result['items'][] = $post_id;

                                    }  else {
                                        $result['duplicates'][] = $post_id;
                                    }



                                } else {

                                    /* Note: If "Update Entries" is selected, the new data will be added if coupon_code does not exists. */

                                    if( $code || $posttitle ) {
                                        /* Add New Entries */
                                        $post_id = wp_insert_post($args);
                                        if( $post_id ){
                                            
                                            /* Custom Fields */
                                            foreach($columns as $j=>$col) {
                                                $subFieldVal = ($values[$j]) ? $values[$j] : '';
                                                /* Google Map Coordinates */
                                                if( in_array($col, $coordinates) ) {
                                                    $selector = 'gmapLatLong_'.$col;
                                                    update_field( $selector, $subFieldVal, $post_id );
                                                } else {
                                                    update_field( $col, $subFieldVal, $post_id );
                                                }
                                            }

                                            $result['items'][] = $post_id;
                                        }
                                    }
                                    
                                }


                            }

                        
                        }

                        $i++;
                  
                   } 
                }


                if($result['duplicates']) {
                    $duplicates = $result['duplicates'];
                    $duplicateCount = count($duplicates);
                    $duplicateMsg = ($duplicateCount>1) ? 'Items already exist.':'Item already exists.';
                    $message = 'Import failed. ' . $duplicateMsg;
                } else {
                    if( $result['items'] ) {
                        $itemsImported = $result['items'];
                        $totalItems = ($itemsImported) ? count($itemsImported) : 0;
                        $txt = ($totalItems>1) ? ' Entries have':'Entry has';
                        $message = $txt . ' been updated successfully.';
                    }
                }

            }
        }
        $result['message'] = $message;
    }


    return $result;
}

function get_property_fieldnames() {
    $custom_fields = array(
        'coupon_code'=>'Coupon Code',
        'community_name'=>'Community Name',
        'address'=>'Address',
        'manager_name'=>'Manager Name',
        'manager_phone'=>'Manager Phone',
        'manager_email'=>'Manager Email',
        'latitude'=>'Latitude',
        'longitude'=>'Longitude'
    );
    return $custom_fields;
}
function cma_search_scripts() {
    // Add Main CSS
    wp_enqueue_style( 'cma-search-main-style', plugins_url() . '/cma-import-data/css/style.css', null, true);
    wp_enqueue_script( 'cma-search-main-script', plugins_url() . '/cma-import-data/js/main.js', array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'cma_search_scripts');

function cma_admin_search_scripts(){
    wp_enqueue_style( 'cma-admin-search-main-style', plugins_url() . '/cma-import-data/css/admin-style.css', null, true);
    wp_enqueue_script( 'cma-search-main-script', plugins_url() . '/cma-import-data/js/admin-scripts.js', array('jquery'), false, true);
}
add_action( 'admin_enqueue_scripts', 'cma_admin_search_scripts' );


/* Add Properties Custom Post. This function can add multiple custom posts if needed. */
add_action('init', 'cma_cpt_init', 1);
function cma_cpt_init() {
    $post_types = array(
        array(
            'post_type' => 'properties',
            'menu_name' => 'Properties',
            'plural'    => 'Properties',
            'single'    => 'Property',
            'menu_icon' => 'dashicons-admin-multisite',
            'supports'  => array('title','editor','thumbnail')
        )
    );
    
    if($post_types) {
        foreach ($post_types as $p) {
            $p_type = ( isset($p['post_type']) && $p['post_type'] ) ? $p['post_type'] : ""; 
            $single_name = ( isset($p['single']) && $p['single'] ) ? $p['single'] : "Custom Post"; 
            $plural_name = ( isset($p['plural']) && $p['plural'] ) ? $p['plural'] : "Custom Post"; 
            $menu_name = ( isset($p['menu_name']) && $p['menu_name'] ) ? $p['menu_name'] : $p['plural']; 
            $menu_icon = ( isset($p['menu_icon']) && $p['menu_icon'] ) ? $p['menu_icon'] : "dashicons-admin-post"; 
            $supports = ( isset($p['supports']) && $p['supports'] ) ? $p['supports'] : array('title','editor','custom-fields','thumbnail'); 
            $taxonomies = ( isset($p['taxonomies']) && $p['taxonomies'] ) ? $p['taxonomies'] : array(); 
            $parent_item_colon = ( isset($p['parent_item_colon']) && $p['parent_item_colon'] ) ? $p['parent_item_colon'] : ""; 
            $menu_position = ( isset($p['menu_position']) && $p['menu_position'] ) ? $p['menu_position'] : 20; 
            
            if($p_type) {
                
                $labels = array(
                    'name' => _x($plural_name, 'post type general name'),
                    'singular_name' => _x($single_name, 'post type singular name'),
                    'add_new' => _x('Add New', $single_name),
                    'add_new_item' => __('Add New ' . $single_name),
                    'edit_item' => __('Edit ' . $single_name),
                    'new_item' => __('New ' . $single_name),
                    'view_item' => __('View ' . $single_name),
                    'search_items' => __('Search ' . $plural_name),
                    'not_found' =>  __('No ' . $plural_name . ' found'),
                    'not_found_in_trash' => __('No ' . $plural_name . ' found in Trash'), 
                    'parent_item_colon' => $parent_item_colon,
                    'menu_name' => $menu_name
                );
            
            
                $args = array(
                    'labels' => $labels,
                    'public' => true,
                    'publicly_queryable' => true,
                    'show_ui' => true, 
                    'show_in_menu' => true, 
                    'show_in_rest' => true,
                    'query_var' => true,
                    'rewrite' => true,
                    'capability_type' => 'post',
                    'has_archive' => false, 
                    'hierarchical' => false, // 'false' acts like posts 'true' acts like pages
                    'menu_position' => $menu_position,
                    'menu_icon'=> $menu_icon,
                    'supports' => $supports
                ); 
                
                register_post_type($p_type,$args); // name used in query
                
            }
            
        }
    }
}

function cma_display_search_form($atts, $content = null) {
    $a = shortcode_atts( array(
        'perpage' => 20,
        'action' => '' 
    ), $atts );

    require_once plugin_dir_path( __FILE__ ) . 'custom-search.php';
    require_once plugin_dir_path( __FILE__ ) . 'search-results.php';

}
add_shortcode('cma-search-form', 'cma_display_search_form');

function cma_update_post_meta( $post_id, $field_name, $value = '' ) {
    if ( empty( $value ) || ! $value )
    {
        delete_post_meta( $post_id, $field_name );
    }
    elseif ( ! get_post_meta( $post_id, $field_name ) )
    {
        add_post_meta( $post_id, $field_name, $value );
    }
    else
    {
        update_post_meta( $post_id, $field_name, $value );
    }
}

function check_code_exists( $coupon_code ) {
    if($coupon_code) {
        $args = array(
            'post_type'         => 'properties',
            'posts_per_page'    => -1,    
            'post_status'       => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'),
            'meta_query'        => array(
                                    array(
                                         'key' => 'coupon_code', 
                                         'value' => $coupon_code,
                                         'compare' => '='
                                     )
                                )
        );

        $records = get_posts($args);
        return ( $records ) ? $records[0]->ID  : '';
    } else {
        return '';
    }
}


function activate(){ 
    flush_rewrite_rules();
}

function deactivate(){
    flush_rewrite_rules();
}


register_activation_hook( __FILE__, 'activate' );

register_deactivation_hook( __FILE__, 'deactivate' );


add_filter( 'page_template', 'wpa_search_page_template' );
function wpa_search_page_template( $page_template )
{
    if ( is_page( 'search' ) ) {
        $page_template = dirname( __FILE__ ) . '/search.php';
    }
    return $page_template;
}


function wp_cma_pagination( $custom_query ) {
    //global $wp_query;
        $big = 999999999; // need an unlikely integer
            echo paginate_links( array(
                'base'                  => '%_%',
                'format'                => '?paged=%#%',
                'current'               => max( 1, get_query_var('paged') ),
                'total'                 => $custom_query->max_num_pages,                
                'prev_next'             => true,
                'prev_text'             => __('« Previous'),
                'next_text'             => __('Next »'),
                'type'                  => 'plain',
                
        ) );
}


function wpa66273_disable_canonical_redirect( $query ) {
    if( 'search' == $query->query_vars['pagename'] )
        remove_filter( 'template_redirect', 'redirect_canonical' );
}
add_action( 'parse_query', 'wpa66273_disable_canonical_redirect' );