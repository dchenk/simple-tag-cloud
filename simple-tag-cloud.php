<?php
/*
Plugin Name: Simple Tag Cloud
Plugin URI: https://github.com/dchenk/simple-tag-cloud
Description: Adds a widget for displaying a customized tag cloud.
Version: 0.9.0
Author: widerwebs
Author URI: https://github.com/dchenk
Text Domain: simple-tc
Domain Path: /languages
*/

define('STC_OPTIONS_KEY', 'simple_tag_cloud');

function azc_tc_load_plugin_textdomain(){
	load_plugin_textdomain('simple-tc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
}
add_action('plugins_loaded', 'azc_tc_load_plugin_textdomain');

function azc_tc_load_css(){
	wp_enqueue_style('simple-tag-cloud', plugins_url( 'style.css', __FILE__ ) );
}
add_action('admin_enqueue_scripts', 'azc_tc_load_css');

function simple_tag_cloud_default_options() {
	if (get_option(STC_OPTIONS_KEY) === false) {
		add_option(STC_OPTIONS_KEY, [
			'smallest' => '8',
			'largest' => '22',
			'unit' => 'pt',
			'number' => '45',
			'format' => 'flat',
			'orderby' => 'Name',
			'order' => 'ASC'
		]);
	}
}
register_activation_hook( __FILE__, 'simple_tag_cloud_default_options' );

function stc_plugin_action_links($links) {
	array_unshift($links, '<a href="' . admin_url('tools.php?page=stc-tag-cloud') . '">Settings</a>');
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'stc_plugin_action_links');

function azc_tc_settings() {
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.', 'simple-tc'));
	}

	// Retrieve plugin configuration options from database
	$options = get_option( STC_OPTIONS_KEY );
	?>
	<div class="wrap">
		<fieldset>
			<h2>Simple Tag Cloud <?php _e('Settings', 'simple-tc'); ?></h2>
			<?php if( isset($_GET['settings-updated']) ) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Settings have been saved.') ?></strong></p>
				</div>
			<?php } ?>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="simple_tag_cloud_save_options">
				<input name="page_options" type="hidden" value="smallest, largest, number">

				<?php wp_nonce_field( 'simple_tag_cloud_nonce', 'simple_tag_cloud_nonce' ); ?>
				<p class="description"><?php _e('After you save the settings, make sure you place the Simple Tag Cloud widget in a widget area.', 'simple-tc'); ?></p>
				<table class="form-table">
				<tr><th scope="row">Tags to Exclude</th><td>
					<div class='simple_tag_cloud_scroll'>
						<?php
							global $wpdb;
							$query = "SELECT t.term_id AS `term_id`, t.name AS `name` FROM $wpdb->term_taxonomy tt INNER JOIN $wpdb->terms t On t.term_id = tt.term_id WHERE tt.taxonomy = 'post_tag' ORDER BY t.name";
							$tags_list = $wpdb->get_results($query);
							foreach($tags_list as $tag) {
								echo '<label>' .
									'<input name="tag[' . $tag->term_id . ']" type="checkbox" value="1" ' . checked('1', $options['tag'][$tag->term_id], false) . '>' . $tag->name .
									'</label>';
							}
							unset($_query_result);
						?>
					</div>
				</td></tr>
				<tr><th scope="row"><label for="smallest"><?php _e('Smallest Font Size', 'simple-tc'); ?></label></th><td>
					<input type="text" name="smallest" value="<?php echo esc_html( stripslashes($options['smallest']) ); ?>" class="small-text">
					<p class="description"><?php _e('The size of the tag with the lowest count value', 'simple-tc'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="largest"><?php _e('Largest Font Size', 'simple-tc'); ?></label></th><td>
					<input type="text" name="largest" value="<?php echo esc_html( stripslashes($options['largest']) ); ?>" class="small-text">
					<p class="description"><?php _e('The size of the tag with the highest count value', 'simple-tc'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="unit"><?php _e('Unit', 'simple-tc'); ?></label></th><td>
					<select name="unit">
						<option value="pt" <?php if($options['unit'] == 'pt'){ echo 'selected'; } ?>>pt</option>
						<option value="px" <?php if($options['unit'] == 'px'){ echo 'selected'; } ?>>px</option>
						<option value="em" <?php if($options['unit'] == 'em'){ echo 'selected'; } ?>>em</option>
					</select>
					<p class="description"><?php _e('Unit of measure for the font size values', 'simple-tc'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="number"><?php _e('Number of Tags', 'simple-tc'); ?></label></th><td>
					<input type="text" name="number" value="<?php echo esc_html( stripslashes($options['number']) ); ?>" class="small-text">
					<p class="description"><?php _e('The number of tags to display in the cloud', 'simple-tc'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="format"><?php _e('Cloud Layout', 'simple-tc'); ?></label></th><td>
					<select name="format">
						<option value="flat" <?php if($options['format'] == 'flat'){ echo ' selected'; } ?>>Flat</option>
						<option value="list" <?php if($options['format'] == 'list'){ echo ' selected'; } ?>>List</option>
					</select>
				</td></tr>
				<tr><th scope="row"><label for="orderby"><?php _e('Order By', 'simple-tc'); ?></label></th><td>
					<select name="orderby">
						<option value="name" <?php if($options['orderby'] == 'name'){ echo ' selected'; } ?>>Name</option>
						<option value="count" <?php if($options['orderby'] == 'count'){ echo ' selected'; } ?>>Count</option>
					</select>
				</td></tr>
				<tr><th scope="row"><label for="order"><?php _e('Sort Order', 'simple-tc'); ?></label></th><td>
					<select name="order">
						<option value="ASC" <?php if($options['order'] == 'ASC') { echo ' selected'; } ?>>Ascending</option>
						<option value="DESC" <?php if($options['order'] == 'DESC') { echo ' selected'; } ?>>Descending</option>
						<option value="RAND" <?php if($options['order'] == 'RAND') { echo ' selected'; } ?>>Random</option>
					</select>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div><?php
}

function azc_tc_admin_init() {
	add_action('admin_post_simple_tag_cloud_save_options', 'simple_tag_cloud_save' );
}
add_action('admin_init', 'azc_tc_admin_init' );

function simple_tag_cloud_save() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) ){
		wp_die( __('You do not have permissions to perform this action', 'simple-tc') );
	}
	// Check for nonce field created in the admin form.
	if (!empty($_POST) && check_admin_referer('simple_tag_cloud_nonce', 'simple_tag_cloud_nonce')) {
		$options = get_option(STC_OPTIONS_KEY, []);

		$opt = 'tag';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		} else {
			$options[$opt] = '';
		}

		$opt = 'smallest';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		$opt = 'largest';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		$opt = 'unit';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		$opt = 'number';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		$opt = 'format';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		$opt = 'orderby';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		$opt = 'order';
		if (isset($_POST[$opt])) {
			$options[$opt] = $_POST[$opt];
		}

		// Store updated options array to database
		update_option(STC_OPTIONS_KEY, $options);

		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg([
			'page'             => 'stc-tag-cloud',
			'settings-updated' => '1'
		], admin_url('tools.php')));
		exit;
	}
}

// Register function to be called when widget initialization occurs to create the widget.
function simple_tag_cloud_create_widget() {
	register_widget('simple_tag_cloud');
}
add_action('widgets_init', 'simple_tag_cloud_create_widget' );

// Widget implementation class.
class simple_tag_cloud extends WP_Widget {
	// Constructor function
	function __construct() {
		add_action( 'wp_enqueue_scripts', [$this, 'enqueue']);

		// Widget creation function
		parent::__construct('simple_tag_cloud', 'Simple Tag Cloud',
			['description' => __('A customizable cloud of your tags.', 'simple-tc')]);
	}

	public function enqueue() {
		wp_enqueue_style( 'simple-tc', plugins_url( 'style.css', __FILE__ ), '', '1.0.0' );
	}

	// Render options form.
	function form( $instance ) {
		// Retrieve previous values from instance or set default values if not present.
		$widget_title = (!empty($instance['simple_tag_cloud_title']) ? esc_attr( $instance['simple_tag_cloud_title'] ) : 'Tag Cloud');
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'simple_tag_cloud_title' ); ?>">
			<?php echo 'Widget Title:'; ?>
			<input type="text"
				id="<?php echo $this->get_field_id( 'simple_tag_cloud_title' ); ?>"
				name="<?php echo $this->get_field_name( 'simple_tag_cloud_title' ); ?>"
				value="<?php echo $widget_title; ?>">
			</label>
		</p>

		<?php
	}

	// Function to perform user input validation.
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['simple_tag_cloud_title'] = strip_tags( $new_instance['simple_tag_cloud_title'] );
		return $instance;
	}

	// Function to display widget contents.
	function widget($args, $instance) {
		echo $args['before_widget'];
		echo $args['before_title'];
		$widget_title = !empty($instance['simple_tag_cloud_title']) ? esc_attr($instance['simple_tag_cloud_title']) : 'Tag Cloud';
		echo apply_filters('widget_title', $widget_title);
		echo $args['after_title'];

		$options = get_option(STC_OPTIONS_KEY, []);
		$args = [
			'smallest'  => $options['smallest'],
			'largest'   => $options['largest'],
			'unit'      => $options['unit'],
			'number'    => $options['number'],
			'format'    => $options['format'],
			'orderby'   => strtolower($options['orderby']),
			'order'     => strtoupper($options['order'])
		];

		if (is_array($options['tag'])) {
			$tags = '';
			foreach ($options['tag'] as $key => $v){
				$tags .= $key . ',';
			}
			$args['exclude'] = $tags;
		}
		// Outside of if statement to display when set to exclude but nothing excluded.
		wp_tag_cloud($args);

		echo $args['after_widget'];
	}
}

if (!function_exists('simple_tag_cloud_menu')) {
	function simple_tag_cloud_menu() {
		add_management_page('Simple Tag Cloud', 'Simple Tag Cloud', 'manage_options', 'stc-tag-cloud', 'azc_tc_settings');
	}
	add_action('admin_menu', 'simple_tag_cloud_menu');
}

if (!function_exists('simple_tag_cloud_css')) {
	function simple_tag_cloud_admin_css() {
		wp_enqueue_style('simple-tag-cloud-admin', plugins_url('admin-style.css', __FILE__));
	}
	add_action('admin_head', 'simple_tag_cloud_admin_css');
}

function simple_tag_cloud_uninstall() {
	delete_option(STC_OPTIONS_KEY);
}
register_uninstall_hook(__FILE__, 'simple_tag_cloud_uninstall');

