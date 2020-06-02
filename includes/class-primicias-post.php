<?php 

namespace Primicias\Migration;

require "class-primicias-repository.php";

use Primicias\Repository\Repository as Repository;

class PostRepository extends Repository {
	
	private $postsLength = 0;
	private $postsLeft = 0;
	private $prevChunk = 0;
	private $currentChunk = 0;
	private $currentPosition = 0;
	private $insertError = 0;
	private $countInserted = 0;
	private const CHUNK_CONFIG = 500;
	private const image_base_url = "https://primicias24.s3.us-east-2.amazonaws.com/public/uploads/images/";
	private const pdf_url = "https://primicias24.s3.us-east-2.amazonaws.com/public/uploads/pdf/";

	public function create($newPost) {

		$pdfs = $this->getPdfsFromPost($newPost->id);

		$content = $newPost->content; 		
		if($pdfs != 0) {
			$formatted_component = $this->attachPdfsToContent($pdfs);

			$content .= $formatted_component;
		}

		$postarr = array(

			'ID' 					=> $newPost->id,
			'post_author'           => get_current_user_id(),
	        'post_content'          => $content,
	        'post_date'             => date($newPost->date),
	        'post_title'            => $newPost->title,
	        'post_excerpt'          => $newPost->summary,
	        'post_status'           => 'publish',
	        'post_type'             => 'post',
	        'post_category'         => array($this->setCategory($newPost->section_id)),
	        'import_id'             => 0
		);
		
		$insert_post_action = wp_insert_post( $postarr );

		if(is_wp_error( $insert_post_action )) {
			$this->insertError += 1;
		} else {

			$images = $this->getImagesFromPost($newPost->id);

			if($images != 0) {
				$image_full_path = self::image_base_url . $images->name;
				$this->insertFeaturedImage($image_full_path, $newPost->id);
			}

			$this->countInserted += 1;

			$save_position = $this->currentChunk . "," . $this->prevChunk;
			update_option("primicias-current-position", $save_position);
		}
	}

	public function get($min, $max) {

		$get_conn = $this->db->getConnection();
		$posts = $get_conn->get_results("SELECT * FROM  news ORDER BY news.date DESC LIMIT $min, $max");

		return $posts;

	}

	public function getImagesFromPost($post_id){
		try {
			$get_conn = $this->db->getConnection();
			$images = $get_conn->get_results($get_conn->prepare("SELECT f.name, f.description, f.type FROM file_new as fn  LEFT JOIN files as f on fn.file_id = f.id WHERE fn.new_id = %d AND f.type = 'image' AND fn.type = 'full'", $post_id));
			if(count($images) == 0) {
				return 0;
			} else {
				return $images[0];
			}
			
		} catch(Exception $e) {
			echo "Failed query image(getImagesFromPost): " . $e->getMessage();
		}
		
	}

	public function getImages($min, $max) {
		//$this->makeConnection();
		$get_conn = $this->db->getConnection();
		$images = $get_conn->get_results("SELECT DISTINCT   n.id, n.title, f.name as image_name
											FROM news n 
											join file_new as fn 
											on fn.new_id  = n.id 
											join files as f 
											on f.id = fn.file_id 
											WHERE fn.type = 'full' AND f.type  = 'image'
											ORDER BY n.date DESC
											LIMIT $min, $max");
		return $images;
	}

	public function setCategory($categoryId) {

		$categories = $this->getCategoryById($categoryId);
		$category = $categories[0];
		$category_name = $category->name;
		$exist_category = get_cat_ID($category_name);
		if($exist_category != 0) {
			return $exist_category;
		} else {
			$catarr = array(
				'cat_name' => $category->name
			);

			$newCategoryId = wp_insert_category( $catarr, false );
			return $newCategoryId;

		}
	}

	public function getCategoryById($cat_id){

		$get_conn = $this->db->getConnection();
		$sentence = $get_conn->get_results( $get_conn->prepare("SELECT * FROM  sections WHERE id =  %d", $cat_id));
		return $sentence;

	}

	public function importPosts() {

		$this->makeConnection();

		$postsLength = $this->postsLength == 0 ? $this->setPostsLength() : $this->postsLength;
		$existPositionOption = get_option( "primicias-current-position" );

		if($existPositionOption == false) {
			$settedOption = add_option("primicias-current-position", "0,0");
		}

		$postsLeft = $this->postsLeft;

		if($existPositionOption){
			$options = explode(",", $existPositionOption);
			$this->currentChunk = $options[0];
			$this->prevChunk = $options[1];
		}

		$date_a = new \DateTime('');
		
		if($this->currentChunk < $postsLength) {

			$diff = $postsLength - $this->currentChunk;
			if($diff > self::CHUNK_CONFIG){
				$this->currentChunk = $this->currentChunk + self::CHUNK_CONFIG;
				$posts = $this->get($this->prevChunk, $this->currentChunk);
				$this->insertChunkOfPosts($posts);
				echo "<p>Chunk: ".count($posts).".............. " . $this->prevChunk . " - " . $this->currentChunk  .  "</p>";
				$this->prevChunk = $this->currentChunk;
			} else {
				$this->currentChunk = $this->currentChunk + $diff;
				$posts = $this->get($this->prevChunk, $this->currentChunk);
				$this->insertChunkOfPosts($posts);
				echo "<p>Chunk: ".count($posts).".............. " . $this->prevChunk . " - " . $this->currentChunk  . "</p>";
			}
		}

		$date_b = new \DateTime('');

		$interval = date_diff($date_a,$date_b);

		$this->db->disconnect();

		echo "END.......... time: ".$interval->format('%h:%i:%s')." count: ".$postsLength." inserted: ".$this->countInserted." errors: " . $this->insertError;

	}

	public function setPostsLength() {

		$get_conn = $this->db->getConnection();
		$sentence = $get_conn->get_results("SELECT count(id) as postLength FROM  news");
		$this->postsLength = intval($sentence[0]->postLength);
		return $this->postsLength;
	}

	public function insertChunkOfPosts($arrayPosts) {
		foreach($arrayPosts as $post) {

			$this->create($post);
		}
	}

	public function insertChunkOfPostsImages($arrayPosts) {
		foreach($arrayPosts as $post) {

			$this->create($post);
		}
	}

	public function insertFeaturedImage( $image_url, $post_id  ){
	    $upload_dir = wp_upload_dir();
	    $filename = basename($image_url);
	    $file = $upload_dir['basedir'] . '/images/' . $filename;
	    $wp_filetype = wp_check_filetype($filename, null );
	    $attachment = array(
	        'post_mime_type' => $wp_filetype['type'],
	        'post_title' => sanitize_file_name($filename),
	        'post_content' => '',
	        'post_status' => 'inherit'
	    );
	    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
	    require_once(ABSPATH . 'wp-admin/includes/image.php');
	    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
	    $res2= set_post_thumbnail( $post_id, $attach_id );

	    if( !$res2 ) {
			$this->insertError += 1;
		} else {
			$this->countInserted += 1;
		}
	}

	public function importPostsImages() {
		$this->makeConnection();

		$postsLength = $this->postsLength == 0 ? $this->setPostsLength() : $this->postsLength;
		$existPositionOption = get_option( "primicias-current-position" );
		$postsLeft = $this->postsLeft;

		if($existPositionOption){
			$this->currentPosition = $existPositionOption;
		}

		$date_a = new \DateTime('');
		
		while($this->currentChunk < $postsLength) {
		
			$diff = $postsLength - $this->currentChunk;
			if($diff > self::CHUNK_CONFIG){
				$this->currentChunk = $this->currentChunk + self::CHUNK_CONFIG;
				$images = $this->getImages($this->prevChunk, $this->currentChunk);
				$this->insertChunkOfImages($images);
				echo "<p>Chunk: ".count($images).".............. " . $this->prevChunk . " - " . $this->currentChunk  .  "</p>";
				$this->prevChunk = $this->currentChunk;
			} else {
				$this->currentChunk = $this->currentChunk + $diff;
				$images = $this->getImages($this->prevChunk, $this->currentChunk);
				$this->insertChunkOfImages($images);
				echo "<p>Chunk: ".count($images).".............. " . $this->prevChunk . " - " . $this->currentChunk  . "</p>";
			}
		}

		$date_b = new \DateTime('');

		$interval = date_diff($date_a,$date_b);

		$this->db->disconnect();

		echo "END.......... time: ".$interval->format('%h:%i:%s')." count: ".$postsLength." inserted: ".$this->countInserted." errors: " . $this->insertError;
	}

	public function insertChunkOfImages($arrayImages) {
		foreach($arrayImages as $image) {			
			$post_id = $this->getPostIdFromTitle($image);
			if($post_id != 0) {
				$image_full_path = self::image_base_url . $image->image_name;
				$this->insertFeaturedImage($image_full_path, $post_id);
			} 
			
		}
	}
	
	public function getPostIdFromTitle($image) {
		global $wpdb;
		$post_name = sanitize_title($image->title);
		$post_found = $wpdb->get_results($wpdb->prepare("SELECT id  FROM  wp_posts WHERE post_name = %s", $post_name));
		//var_dump($post_found);
		if(count($post_found) == 0) {
			return 0;
		} else {
			return intval($post_found[0]->id);
		}
	}

	public function getPdfsFromPost($post_id) {
		try {
			
			$get_conn = $this->db->getConnection();
			$query = $get_conn->get_results($get_conn->prepare("SELECT f.name, f.description, f.type FROM file_new as fn  LEFT JOIN files as f on fn.file_id = f.id WHERE fn.new_id = %d AND f.type = 'pdf'", $post_id));
			if(count($query) == 0) {
				return 0;
			} else {
				return $query;
			}
		} catch(Exception $e){
			 echo "Failed query pdf (getPdfsFromPost): " . $e->getMessage();
		}

	}

	public function attachPdfsToContent($arr_pdfs){
		$template = "<br><br>";
		foreach ($arr_pdfs as $pdf) {
			$template .= $this->setPdfComponent($pdf);
		}

		return $template;
	}

	public function setPdfComponent($element) {
		return '<div class="wp-block-file"><a href="'.self::pdf_url.$element->name.'">'.$element->description.'</a><a href="'.self::pdf_url.$element->name.'" class="wp-block-file__button" download="">Descarga</a></div>';
	}
}

 ?>