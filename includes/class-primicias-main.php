<?php  

namespace Primicias\Migration;


require "class-primicias-post.php";

class PrimiciasMain {

    private $postRepository; 	

	function run() {

		$this->postRepository =  new PostRepository();
		$this->postRepository->setDatabase();
	}

	public function __construct () {
		add_action( 'admin_menu', array( $this, 'nprm_register_migration' ));
	 	add_action('admin_enqueue_scripts', array( $this, 'load_custom_wpfb_admin_style'));
	 	//add_action('wp_ajax_start_migration', array($this, 'start_migration'));
	 //	add_action( 'admin_init', array( $this, 'primicias_register_settings_options' ));
	 	add_action( 'admin_post_start_migration', array( $this,'start_migration' ));
	 	add_action( 'admin_post_start_img_migration', array( $this,'start_img_migration' ));
	}

	/*public function primicias_register_settings_options() {
		register_setting( 'primicias-settings-group', 'current-chunk' );
		register_setting( 'primicias-settings-group', 'last-position' );
	}*/

	public function nprm_general_template () {
		require_once dirname( __DIR__ ) . "/templates/migration-screen.php";
	}

	public function nprm_register_migration() {
	 	add_menu_page( "Primicias 24 Migration", "Primicias 24 DB Migration", "import", "primicias24-plugin", array( $this, 'nprm_general_template'),
	 					 'dashicons-palmtree');
	} 

	public function load_custom_wpfb_admin_style(){
	    wp_register_style( 'custom_fbwp_admin_css', plugins_url("/primicias24_migration") . '/assets/css/style.css', false, '1.0.0' );
	    wp_enqueue_style( 'custom_fbwp_admin_css' );
	    wp_enqueue_script(
			  'migration-request',
			  plugins_url("/primicias24_migration") . '/assets/js/migration-request.js',
			  [ 'jquery' ],
			  false,
			  true
			);
	    wp_localize_script(
			  'migration-request',
			  'primicias_ajax_object',
			  [
			    'ajax_url'  => admin_url( 'admin-ajax.php' ),
			    'security'  => wp_create_nonce( 'primicias-security-nonce' ),
			  ]
			);
	}

	public function start_migration() {

		try {

			$result = $this->postRepository->importPosts();

		    wp_die();

		} catch(Exception $e) {
			status_header( 500,  $e->getMessage());
			wp_die();
		}	
		
	}

	public function start_img_migration() {

		try {

			$this->postRepository->importPostsImages();
		    wp_die();

		} catch(Exception $e) {
			status_header( 500,  $e->getMessage());
			wp_die();
		}	
		
	}

	

}