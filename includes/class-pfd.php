<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.fiverr.com/junaidzx90
 * @since      1.0.0
 *
 * @package    Pfd
 * @subpackage Pfd/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pfd
 * @subpackage Pfd/includes
 * @author     junaidzx90 <admin@easeare.com>
 */
class Pfd {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PFD_VERSION' ) ) {
			$this->version = PFD_VERSION;
		} else {
			$this->version = '1.0.2';
		}
		$this->plugin_name = 'pfd';
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		add_action( 'admin_enqueue_scripts', [$this, 'enqueue_styles'] );
		add_action( 'admin_menu', [$this, 'pfd_admin_menu'] );
		add_action( 'init', [$this, 'save_pfd_lines'] );

		// Cron functionality
		add_action( 'pfd_cat_replace_cron', [$this, 'pfd_cat_replace_cron_cb'] );
		add_filter( 'cron_schedules', [$this, 'pfd_cat_replace_half_hour'] );

		add_action( "wp_ajax_get_course_posts", [$this, "get_course_posts"] );
		add_action( "wp_ajax_nopriv_get_course_posts", [$this, "get_course_posts"] );
	}

	// wp cron schedules
	function pfd_cat_replace_half_hour( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['half_hour'] = array(
			'interval' => 60*30,
			'display'  => __( 'Half hour' ),
		);
		return $schedules;
	}

	function enqueue_styles(){
		wp_enqueue_style( 'select2', PFD_URL.'partials/select2.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name, PFD_URL.'partials/pfd.css', array(), $this->version, 'all' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'select2', PFD_URL.'partials/select2.min.js', array(), $this->version, false );
		wp_enqueue_script( $this->plugin_name, PFD_URL.'partials/pfd.js', array('jquery', 'select2'), $this->version, true );

		wp_localize_script( $this->plugin_name, 'pfdajax', array(
			'ajaxurl' => admin_url( "admin-ajax.php" ),
			'nonce' => wp_create_nonce( 'pfd_nonce' ),
			'categories' => $this->get_categories(),
			'posts' => $this->get_posts()
		) );
	}

	function get_posts(){
		$posts = get_posts(['post_type' => 'post', 'numberposts' => -1]);
		$data = [];
		if($posts){
			foreach($posts as $post){
				$arr = [
					'id' => $post->ID,
					'text' => $post->post_title
				];
				$data[] = $arr;
			}
		}
		return $data;
	}

	function pfd_admin_menu(){
		add_menu_page( 'Post for a day', 'Post for a Day', 'manage_options', 'pfd', [$this, 'pfd_menu_page_cb'], 'dashicons-clock', 45 );
	}

	function pfd_menu_page_cb(){
		if(isset($_GET['page']) && $_GET['page'] === 'pfd' && (isset($_GET['action']) && $_GET['action'] === 'edit') || isset($_GET['action']) && $_GET['action'] === 'add'){
			require_once plugin_dir_path( __FILE__ ).'templates/edit-page.php';
		}else{
			$courses = new PFD_Courses();
			?>
			<form action="" method="post">
				<div class="wrap" id="courses-table">
					<div class="addnew_box">
						<h3 class="heading3">Courses</h3>
						<a href="?page=pfd&action=add" class="button-secondary">Add new</a>
					</div>
					<hr>
					<?php $courses->prepare_items(); ?>
					<?php $courses->display(); ?>
				</div>
			</form>
			<?php
		}
	}

	function get_categories(){
		$categores = get_categories(['hide_empty' => false]);
		$data = [];
		if($categores){
			foreach($categores as $category){
				$arr = [
					'id' => $category->term_id,
					'text' => $category->name
				];
				$data[] = $arr;
			}
		}
		return $data;
	}

	function save_pfd_lines(){
		global $wpdb;

		if(isset($_POST['deletedLines'])){
			$lines = $_POST['deletedLines'];
			foreach($lines as $line){
				$wpdb->query("DELETE FROM {$wpdb->prefix}pfd_lines WHERE ID = $line");
			}
		}

		if(isset($_POST['pfd_form_btn'])){
			if(isset($_POST['course_tag']) && isset($_POST['course_date']) && isset($_POST['course_duration']) && isset($_POST['course_workingdays']) && isset($_POST['course_temp_cat'])){
				$courseID = 0;
				if(isset($_POST['courseID'])){
					$courseID = intval($_POST['courseID']);
				}
				
				$course_name = sanitize_text_field($_POST['course_name']);
				$course_tag = sanitize_text_field($_POST['course_tag']);
				$course_date = $_POST['course_date'];
				$course_duration = intval($_POST['course_duration']);
				$workingdays = intval($_POST['course_workingdays']);
				$course_temp_cat = intval($_POST['course_temp_cat']);

				$course_duration = (($course_duration) ? $course_duration : 24);
				$workingdays = (($workingdays) ? $workingdays : 7);
				if(empty($course_temp_cat)){
					$tempCat = get_category_by_slug( 'first' );
					$course_temp_cat = $tempCat->term_id;
				}

				if($courseID > 0){
					$wpdb->update($wpdb->prefix.'pfd_courses', array(
						'name' => $course_name,
						'course_tag' => $course_tag,
						'date' => $course_date,
						'duration' => $course_duration,
						'working_days' => $workingdays,
						'temp_category' => $course_temp_cat
					),array("ID" => $courseID), array('%s', '%s', '%s','%d','%d','%d'), array('%d'));
				}else{
					$wpdb->insert($wpdb->prefix.'pfd_courses', array(
						'name' => $course_name,
						'course_tag' => $course_tag,
						'date' => $course_date,
						'duration' => $course_duration,
						'working_days' => $workingdays,
						'temp_category' => $course_temp_cat,
						'created' => date("d-m-y h:i:s a")
					), array('%s', '%s', '%s','%d','%d','%d','%s'));

					$courseID = $wpdb->insert_id;
				}

				if(isset($_POST['pfdposts'])){
					$pfds = $_POST['pfdposts'];

					$post_ids = array();

					if(sizeof($pfds) > 0){
						$lines = array();
						foreach($pfds as $data){
							$post_id = intval($data['post']);
							$post_ids[] = $post_id;

							$date = $data['date'];
							$duration = (($data['duration']) ? intval($data['duration']) : 24 );
							$category = intval($data['category']);

							$origCats = get_the_category($post_id);
							if($origCats){
								$origCats = wp_list_pluck( $origCats, 'term_id' );
							}

							$originalCat = $wpdb->get_var("SELECT course_lines FROM {$wpdb->prefix}pfd_courses WHERE ID = $courseID");

							if($originalCat){
								$originalCat = unserialize($originalCat);
								$arraind = array_search($post_id, array_column($originalCat, "post_id") );
								if(is_array($originalCat[$arraind]['original_categories']) && sizeof($originalCat[$arraind]['original_categories']) > 0){
									$origCats = $originalCat[$arraind]['original_categories'];
								}
							}

							$linearr = array(
								'post_id' => $post_id,
								'course_id' => $courseID,
								'temp_category' => $category,
								'original_categories' => $origCats,
								'duration' => $duration,
								'date' => date("y-m-d", strtotime($date))
							);

							$lines[] = $linearr;
						}

						$wpdb->update($wpdb->prefix.'pfd_courses', array(
							'course_lines' => serialize($lines),
						),array("ID" => $courseID), array('%s'), array('%d'));
					}

					$this->pfd_cat_replace_cron_cb();
					wp_safe_redirect( admin_url( "admin.php?page=pfd&action=edit&id=$courseID" ) );
					exit;
				}
			}
		}
	}

	function get_line_status($date, $duration){
		// date_default_timezone_set('Asia/Dhaka');
		date_default_timezone_set('CET');

		$currentDate = date('Y-m-d h:i:s a', time());

		$date = strtotime($date);
		// hour to timestamps
		$duration = $duration*3600;
		// audition times with date and subtruct 1 sec
		$date = $date+$duration-60;
		$date = date("Y-m-d h:i:s a", $date);

		if(strtotime($currentDate) <= strtotime($date)){
			return true;
		}else{
			return false;
		}
	}

	function pfd_cat_replace_cron_cb(){
		global $wpdb;
		$lines = null;
		$courseData = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pfd_courses");
		

		if($courseData){
			foreach($courseData as $course){
				$lines = $course->course_lines;
				$lines = (( $lines ) ? unserialize( $lines ) : null);

				if($lines){
					foreach($lines as $line){
						$post_ID = $line['post_id'];
						$date = $line['date'];
						$duration = intval($line['duration']);
		
						$lineStatus = $this->get_line_status($date, $duration);
		
						$tempCat = $line['temp_category'];
						$org_categories = $line['original_categories'];
		
						if($lineStatus){
							wp_remove_object_terms( $post_ID, $org_categories, 'category' );
							wp_set_object_terms( $post_ID, intval( $tempCat ), 'category' );
						}else{
							wp_remove_object_terms( $post_ID, intval( $tempCat ), 'category' );
							wp_set_object_terms( $post_ID, $org_categories, 'category' );
						}
					}
				}
			}
		}
	}

	function get_dates($days, $date){
		$datestamp = strtotime('+1 day', strtotime($date));
		$weekString = date("D", $datestamp);
		$newdate = '';
		$newdate = date("Y-m-d", $datestamp);

		if($days === 5){
			if($weekString === "Sat"){
				$datestamp = strtotime('+1 day', $datestamp);
				$weekString = date("D", $datestamp);
				$newdate = date("Y-m-d", $datestamp);
				if($weekString === "Sun"){
					$datestamp = strtotime('+1 day', $datestamp);
					$weekString = date("D", $datestamp);
					$newdate = date("Y-m-d", $datestamp);
				}
			}
		}

		return $newdate;
	}

	// Ajax call
	function get_course_posts(){
		if(!wp_verify_nonce( $_GET['nonce'], "pfd_nonce" )){
			die("Invalid Request");
		}

		if(isset($_GET["course"])){
			$courseData = $_GET["course"];

			$course_tag = $courseData['course_tag'];

			$course_date = $courseData['course_date'];

			$course_duration = $courseData['course_duration'];
			$course_duration = (($course_duration) ? $course_duration : 24);

			$course_temp_cat = $courseData['course_temp_cat'];
			if(empty($course_temp_cat)){
				$tempCat = get_category_by_slug( 'first' );
				$course_temp_cat = $tempCat->term_id;
			}

			$workingdays = $courseData['course_workingdays'];
			$workingdays = (($workingdays) ? intval($workingdays) : 7);

			$args = array( 
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy'  => 'post_tag',
						'field'     => 'slug',
						'terms'     => sanitize_title( $course_tag )
					)
				),
				'orderby' => 'title',
				'order' => 'ASC',
				'fields' => 'ids'
			);
		
			$coursePosts = get_posts( $args );

			$data = array();

			if($coursePosts){
				$newdate = '';

				$date = $course_date;
				$datestamp = strtotime('-1 day', strtotime($date));
				$date = date("Y-m-d", $datestamp);

				foreach($coursePosts as $post_id){
					$ptitle = get_the_title( $post_id );
					
					if(preg_match("/#/", $ptitle )){
						$date = $this->get_dates($workingdays, $date);

						$arr = array(
							'post_id' => $post_id,
							'post_title' => $ptitle,
							'tag' => $course_tag,
							'date' => $date,
							'duration' => $course_duration,
							'temp_cat' => $course_temp_cat,
							'cat_text' => get_the_category_by_ID( intval($course_temp_cat) )
						);
						
						$data[] = $arr;
					}
					
				}
				echo json_encode(array("success" => $data));
				die;
			}
			echo json_encode(array("error" => "No posts found for this course!"));
			die;
		}
	}
}
