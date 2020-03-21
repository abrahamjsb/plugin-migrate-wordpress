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
	private const CHUNK_CONFIG = 100;

	public function create($newPost) {

		$postarr = array(

			'post_author'           => get_current_user_id(),
	        'post_content'          => $newPost->content,
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
			$this->countInserted += 1;
		}
	}

	public function update() {

	}

	public function get($min, $max) {

		//connecting database
		//$get_conn = $this->makeConnection();
		$get_conn = $this->db->getConnection();
		/*$posts = $get_conn->get_results(
								$get_conn->prepare("SELECT * FROM  news ORDER BY news.date DESC LIMIT %d, %d", 
								array($min, $max)
							));*/
		$posts = $get_conn->get_results("SELECT * FROM  news ORDER BY news.date DESC LIMIT $min, $max");

		return $posts;

	}

	public function delete() {

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
		$postsLeft = $this->postsLeft;

		if($existPositionOption){
			$this->currentPosition = $existPositionOption;
		}

		$date_a = new \DateTime('');
		
		while($this->currentChunk < $postsLength) {

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

	

}

 ?>