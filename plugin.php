<?php

/*
Plugin Name: WP_Form_Post_to_DB
Plugin URI: https://bonddesign.com.ua/
Description: Demo
Version: 1.0
Author: bonddesign
Author URI:  https://bonddesign.com.ua/
Text Domain: wpfpdb
*/

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Act on plugin activation
register_activation_hook( __FILE__, "activate_wpfpdb" );

// Act on plugin de-activation
register_deactivation_hook( __FILE__, "deactivate_wpfpdb" );

// Activate Plugin
function activate_wpfpdb() {
	// Execute tasks on Plugin activation
	enqueue_script();
	init_fom();
	// Insert DB Tables
	init_db_wpfpdb();
}

// De-activate Plugin
function deactivate_wpfpdb() {

	// Execute tasks on Plugin de-activation
}

// Initialize DB Tables
function init_db_wpfpdb() {

	// WP Globals
	global $table_prefix, $wpdb;

	// Form Table
	$formTable = $table_prefix . 'form';

	// Create Form Table if not exist
	if( $wpdb->get_var( "show tables like '$formTable'" ) != $formTable ) {

		// Query - Create Table
		$sql = "CREATE TABLE `$formTable` (";
		$sql .= " `id` int(11) NOT NULL auto_increment, ";
		$sql .= " `name` varchar(500) NOT NULL, ";
		$sql .= " `email` varchar(500) NOT NULL, ";
		$sql .= " `phone` varchar(150) NOT NULL, ";
		$sql .= " `date` varchar(150), ";
		$sql .= " PRIMARY KEY `id` (`id`) ";
		$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";

		// Include Upgrade Script
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	
		// Create Table
		dbDelta( $sql );
	}
}

function init_fom(){
echo '<form id="wpfpdb_form">
      <label for="name">Name:</label>
      <input type="text" id="name" name="user_name">
      <label for="mail">E-mail:</label>
      <input type="email" id="email" name="user_mail">
	  <label for="phone">Phone:</label>
      <input type="phone" id="phone" name="user_phone">
	  <button type="submit" id="submit">Send your message</button>
</form>';
}
add_shortcode('form','init_fom');

function enqueue_script(){   
    wp_enqueue_script( 'script', plugins_url('/', __FILE__ ).'script.js', '', false );
	wp_localize_script( 'script', 'ajaxurl',
		array(
			'url' => admin_url('admin-ajax.php')
		)
	);
}
add_action('wp_enqueue_scripts', 'enqueue_script');

add_action('wp_ajax_nopriv_add_form','add_form');
add_action('wp_ajax_add_form','add_form');
function add_form(){
	global $wpdb;
	$name = sanitize_text_field($_POST["name"]);
	$email = sanitize_text_field($_POST["email"]);
	$phone = intval($_POST["phone"]);
	if(!empty($name )){
	$insert_row = $wpdb->insert('wp_form', array(
		'name' => $name,
		'email' => $email,
		'phone' => $phone,
		'date' => date("Y-m-d")
	));
	}
	if($insert_row){
    echo json_encode(array('res'=>true, 'message'=>__('New row has been inserted.')));
}else{
    echo json_encode(array('res'=>false, 'message'=>__('Something went wrong. Please try again later.')));
}
wp_die();
}

class WP_Form extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Form', 'wpfpdb' ), //singular name of the listed records
			'plural'   => __( 'Forms', 'wpfpdb' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}


	/**
	 * Retrieve Forms data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_forms( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}form";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a form record.
	 *
	 * @param int $id form ID
	 */
	public static function delete_form( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}form",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}form";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no form data is available */
	public function no_items() {
		_e( 'No form avaliable.', 'wpfpdb' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'email':
			case 'date':
			case 'phone':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'cp_delete_form' );

		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&form=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Name', 'wpfpdb' ),
			'email'   => __( 'Email', 'wpfpdb' ),
			'phone'   => __( 'Phone', 'wpfpdb' ),
			'date'    => __( 'Date', 'wpfpdb' ),
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'forms_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_forms( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'cp_delete_form' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_form( absint( $_GET['form'] ) );

		                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		                // add_query_arg() return the current url
		                wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_form( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}

}


class wpfpdb_Plugin {

	// class instance
	static $instance;

	// form WP_List_Table object
	public $form_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			'WP_Form',
			'WP_Form',
			'manage_options',
			'wp_list_table_class',
			[ $this, 'plugin_settings_page' ]
		);

		add_action( "load-$hook", [ $this, 'screen_option' ] );

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap">
			<h2>WP_Form</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->form_obj->prepare_items();
								$this->form_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'forms',
			'default' => 5,
			'option'  => 'forms_per_page'
		];

		add_screen_option( $option, $args );

		$this->form_obj = new WP_Form();
	}


	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}


add_action( 'plugins_loaded', function () {
	wpfpdb_Plugin::get_instance();
} );
