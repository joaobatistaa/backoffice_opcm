<?php

require_once("Rest.inc.php");
require_once("db.php");

class PasswordHash {
	var $itoa64;
	var $iteration_count_log2;
	var $portable_hashes;
	var $random_state;


	function __construct( $iteration_count_log2, $portable_hashes )
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
			$iteration_count_log2 = 8;
		$this->iteration_count_log2 = $iteration_count_log2;

		$this->portable_hashes = $portable_hashes;

		$this->random_state = microtime() . uniqid(rand(), TRUE); 
	}

	public function PasswordHash( $iteration_count_log2, $portable_hashes ) {
		self::__construct( $iteration_count_log2, $portable_hashes );
	}

	function get_random_bytes($count)
	{
		$output = '';
		if ( @is_readable('/dev/urandom') &&
		    ($fh = @fopen('/dev/urandom', 'rb'))) {
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state =
				    md5(microtime() . $this->random_state);
				$output .=
				    pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}

	function encode64($input, $count)
	{
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $this->itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;
			$output .= $this->itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;
			if ($i < $count)
				$value |= ord($input[$i]) << 16;
			$output .= $this->itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;
			$output .= $this->itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}

	function gensalt_private($input)
	{
		$output = '$P$';
		$output .= $this->itoa64[min($this->iteration_count_log2 +
			((PHP_VERSION >= '5') ? 5 : 3), 30)];
		$output .= $this->encode64($input, 6);

		return $output;
	}

	function crypt_private($password, $setting)
	{
		$output = '*0';
		if (substr($setting, 0, 2) == $output)
			$output = '*1';

		$id = substr($setting, 0, 3);
		
		if ($id != '$P$' && $id != '$H$')
			return $output;

		$count_log2 = strpos($this->itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = substr($setting, 4, 8);
		if (strlen($salt) != 8)
			return $output;

		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, TRUE);
			do {
				$hash = md5($hash . $password, TRUE);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);

		return $output;
	}

	function gensalt_extended($input)
	{
		$count_log2 = min($this->iteration_count_log2 + 8, 24);

		$count = (1 << $count_log2) - 1;

		$output = '_';
		$output .= $this->itoa64[$count & 0x3f];
		$output .= $this->itoa64[($count >> 6) & 0x3f];
		$output .= $this->itoa64[($count >> 12) & 0x3f];
		$output .= $this->itoa64[($count >> 18) & 0x3f];

		$output .= $this->encode64($input, 3);

		return $output;
	}

	function gensalt_blowfish($input)
	{

		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '$2a$';
		$output .= chr(ord('0') + $this->iteration_count_log2 / 10);
		$output .= chr(ord('0') + $this->iteration_count_log2 % 10);
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

	function HashPassword($password)
	{
		if ( strlen( $password ) > 4096 ) {
			return '*';
		}

		$random = '';

		if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
			$random = $this->get_random_bytes(16);
			$hash =
			    crypt($password, $this->gensalt_blowfish($random));
			if (strlen($hash) == 60)
				return $hash;
		}

		if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
			if (strlen($random) < 3)
				$random = $this->get_random_bytes(3);
			$hash =
			    crypt($password, $this->gensalt_extended($random));
			if (strlen($hash) == 20)
				return $hash;
		}

		if (strlen($random) < 6)
			$random = $this->get_random_bytes(6);
		$hash =
		    $this->crypt_private($password,
		    $this->gensalt_private($random));
		if (strlen($hash) == 34)
			return $hash;

		return '*';
	}

	function CheckPassword($password, $stored_hash)
	{
		if ( strlen( $password ) > 4096 ) {
			return false;
		}

		$hash = $this->crypt_private($password, $stored_hash);
		if ($hash[0] == '*')
			$hash = crypt($password, $stored_hash);

		return $hash === $stored_hash;
	}
}


class functions extends REST {
    
    private $mysqli = NULL;
    private $db = NULL;
    
    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
    }

	public function checkConnection() {
			if (mysqli_ping($this->mysqli)) {
                $respon = array(
                    'status' => 'ok', 'database' => 'connected'
                );
                $this->response($this->json($respon), 200);
			} else {
                $respon = array(
                    'status' => 'failed', 'database' => 'not connected'
                );
                $this->response($this->json($respon), 404);
			}
	}


    public function getRecentPosts() {

    		include "../includes/config.php";
		    $setting_qry    = "SELECT * FROM tbl_settings where id = '1'";
		    $setting_result = mysqli_query($connect, $setting_qry);
		    $settings_row   = mysqli_fetch_assoc($setting_result);
		    $api_key    = $settings_row['api_key'];

			if (isset($_GET['api_key'])) {

				$access_key_received = $_GET['api_key'];

				if ($access_key_received == $api_key) {

					if($this->get_request_method() != "GET") $this->response('',406);
						$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
						$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
						
						$offset = ($page * $limit) - $limit;
						$count_total = $this->get_count_result("SELECT COUNT(DISTINCT n.nid) FROM tbl_news n WHERE n.content_type = 'Post' ");
						$query = "SELECT DISTINCT n.nid, 
									n.news_title, 
									n.cat_id,
									n.news_date, 
									n.news_image, 
									n.news_description,
									n.video_url,
									n.video_id, 
									n.content_type, 
									
									c.category_name, 
									COUNT(DISTINCT r.comment_id) as comments_count

								  FROM tbl_news n 

								  LEFT JOIN tbl_comments r ON n.nid = r.nid 
								  LEFT JOIN tbl_category c ON n.cat_id = c.cid

								  WHERE n.content_type = 'Post'

								  GROUP BY n.nid 

								  ORDER BY n.nid 

								  DESC LIMIT $limit OFFSET $offset";

						$categories = $this->get_list_result($query);
						$count = count($categories);
						$respon = array(
							'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $categories
						);
						$this->response($this->json($respon), 200);

				} else {
					$respon = array( 'status' => 'failed', 'message' => 'Oops, API Key is Incorrect!');
					$this->response($this->json($respon), 404);
				}
			} else {
				$respon = array( 'status' => 'failed', 'message' => 'Forbidden, API Key is Required!');
				$this->response($this->json($respon), 404);
			}

    }
    
    public function getRecentEvents() {

    	include "../includes/config.php";
		    $setting_qry    = "SELECT * FROM tbl_settings where id = '1'";
		    $setting_result = mysqli_query($connect, $setting_qry);
		    $settings_row   = mysqli_fetch_assoc($setting_result);
		    $api_key    = $settings_row['api_key'];

			if (isset($_GET['api_key'])) {

				$access_key_received = $_GET['api_key'];

				if ($access_key_received == $api_key) {

					if($this->get_request_method() != "GET") $this->response('',406);
						$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
						$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
						
						$offset = ($page * $limit) - $limit;
						$count_total = $this->get_count_result("SELECT COUNT(DISTINCT n.eid) FROM tbl_events n");
						$query = "SELECT DISTINCT n.eid, 
									n.event_title, 
									n.event_image,
									n.event_datainicial, 
									n.event_datafinal, 
									n.event_description,
									n.event_moreinfo,
									n.event_localname,
									n.event_video,
									n.event_videoid, 
									n.event_pessoas, 
									
									COUNT(n.eid) as eid_count

								  FROM tbl_events n 

								  GROUP BY n.eid 

								  ORDER BY n.eid 

								  DESC LIMIT $limit OFFSET $offset";

						$categories = $this->get_list_result($query);
						$count = count($categories);
						$respon = array(
							'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $categories
						);
						$this->response($this->json($respon), 200);

				} else {
					$respon = array( 'status' => 'failed', 'message' => 'Oops, API Key is Incorrect!');
					$this->response($this->json($respon), 404);
				}
			} else {
				$respon = array( 'status' => 'failed', 'message' => 'Forbidden, API Key is Required!');
				$this->response($this->json($respon), 404);
			}


    }

	public function getNewsById() {

		include "../includes/config.php";

		$id = $_GET['id'];
		
		$sql = "SELECT DISTINCT n.nid, 
						n.news_title, 
						n.cat_id,
						n.news_date, 
						n.news_image, 
						n.news_description,
						n.video_url,
						n.video_id, 
						n.content_type, 
									
						c.category_name, 
						COUNT(DISTINCT r.comment_id) as comments_count

						FROM tbl_news n 

						LEFT JOIN tbl_comments r ON n.nid = r.nid 
						LEFT JOIN tbl_category c ON n.cat_id = c.cid 

						WHERE n.nid = $id

						GROUP BY n.nid
								 
						LIMIT 1";
		$result = mysqli_query($connect, $sql);

		header( 'Content-Type: application/json; charset=utf-8' );
		print json_encode(mysqli_fetch_assoc($result));


	}    
	
	public function getEventsById() {

		include "../includes/config.php";

		$id = $_GET['id'];
		
		$sql = "SELECT DISTINCT n.eid, 
						n.event_title, 
						n.event_image,
						n.event_datainicial, 
						n.event_datafinal, 
						n.event_description,
						n.event_moreinfo,
						n.event_localname, 
						n.event_video,
						n.event_videoid,
						n.event_pessoas, 
						
						COUNT(n.eid) as eid_count

						FROM tbl_events n

						WHERE n.eid = $id
						
						GROUP BY n.eid
								 
						LIMIT 1";
						
		$result = mysqli_query($connect, $sql);

		header( 'Content-Type: application/json; charset=utf-8' );
		print json_encode(mysqli_fetch_assoc($result));


	}    

	public function getNewsDetail() {

    	$id = $_GET['id'];

		if($this->get_request_method() != "GET") $this->response('',406);

		$query_post = "SELECT DISTINCT n.nid, 
						n.news_title, 
						n.cat_id,
						n.news_date, 
						n.news_image, 
						n.news_description,
						n.video_url,
						n.video_id, 
						n.content_type, 
									
						c.category_name, 
						COUNT(DISTINCT r.comment_id) as comments_count

						FROM tbl_news n 

						LEFT JOIN tbl_comments r ON n.nid = r.nid 
						LEFT JOIN tbl_category c ON n.cat_id = c.cid 

						WHERE n.nid = $id

						GROUP BY n.nid
								 
						LIMIT 1";

		$post = $this->get_one($query_post);
		$count = count($post);
		$respon = array(
			'status' => 'ok', 'post' => $post
		);
		$this->response($this->json($respon), 200);

    }
    
    public function getEventsDetail() {

    	$id = $_GET['id'];

		if($this->get_request_method() != "GET") $this->response('',406);

		$query_post = "SELECT DISTINCT n.eid, 
						n.event_title, 
						n.event_image,
						n.event_datainicial, 
						n.event_datafinal, 
						n.event_description,
						n.event_moreinfo,
						n.event_localname, 
					    n.event_video,
						n.event_videoid,
						n.event_pessoas, 
						
						COUNT(n.eid) as eid_count

						FROM tbl_events n
						
						WHERE n.eid = $id

						GROUP BY n.eid
								 
						LIMIT 1";

		$post = $this->get_one($query_post);
		$count = count($post);
		$respon = array(
			'status' => 'ok', 'post' => $post
		);
		$this->response($this->json($respon), 200);

    }

    public function getVideoPosts() {

    		include "../includes/config.php";
		    $setting_qry    = "SELECT * FROM tbl_settings where id = '1'";
		    $setting_result = mysqli_query($connect, $setting_qry);
		    $settings_row   = mysqli_fetch_assoc($setting_result);
		    $api_key    = $settings_row['api_key'];

			if (isset($_GET['api_key'])) {

				$access_key_received = $_GET['api_key'];

				if ($access_key_received == $api_key) {

					if($this->get_request_method() != "GET") $this->response('',406);
						$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
						$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
						
						$offset = ($page * $limit) - $limit;
						$count_total = $this->get_count_result("SELECT COUNT(DISTINCT n.nid) FROM tbl_news n WHERE n.content_type != 'Post' ");
						$query = "SELECT DISTINCT n.nid, 
									n.news_title, 
									n.cat_id,
									n.news_date, 
									n.news_image, 
									n.news_description,
									n.video_url,
									n.video_id, 
									n.content_type, 
									
									c.category_name, 
									COUNT(DISTINCT r.comment_id) as comments_count

								  FROM tbl_news n 

								  LEFT JOIN tbl_comments r ON n.nid = r.nid 
								  LEFT JOIN tbl_category c ON n.cat_id = c.cid

								  WHERE n.content_type != 'Post'

								  GROUP BY n.nid 

								  ORDER BY n.nid 

								  DESC LIMIT $limit OFFSET $offset";

						$categories = $this->get_list_result($query);
						$count = count($categories);
						$respon = array(
							'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $categories
						);
						$this->response($this->json($respon), 200);

				} else {
					$respon = array( 'status' => 'failed', 'message' => 'Oops, API Key is Incorrect!');
					$this->response($this->json($respon), 404);
				}
			} else {
				$respon = array( 'status' => 'failed', 'message' => 'Forbidden, API Key is Required!');
				$this->response($this->json($respon), 404);
			}

    }
    
    public function getCategoryIndex() {

    	include "../includes/config.php";
        $setting_qry    = "SELECT * FROM tbl_settings where id = '1'";
		$setting_result = mysqli_query($connect, $setting_qry);
		$settings_row   = mysqli_fetch_assoc($setting_result);
		$api_key    = $settings_row['api_key'];

			if (isset($_GET['api_key'])) {

				$access_key_received = $_GET['api_key'];

				if ($access_key_received == $api_key) {

					if($this->get_request_method() != "GET") $this->response('',406);
					$count_total = $this->get_count_result("SELECT COUNT(DISTINCT cid) FROM tbl_category");

					//$query = "SELECT distinct cid, category_name, category_image FROM tbl_category ORDER BY cid DESC";
					$query = "SELECT DISTINCT c.cid, c.category_name, c.category_image, COUNT(DISTINCT r.nid) as post_count
					  FROM tbl_category c LEFT JOIN tbl_news r ON c.cid = r.cat_id GROUP BY c.cid ORDER BY c.cid DESC";

					$news = $this->get_list_result($query);
					$count = count($news);
					$respon = array(
						'status' => 'ok', 'count' => $count, 'categories' => $news
					);
					$this->response($this->json($respon), 200);

				} else {
					$respon = array( 'status' => 'failed', 'message' => 'Oops, API Key is Incorrect!');
					$this->response($this->json($respon), 404);
				}
			} else {
				$respon = array( 'status' => 'failed', 'message' => 'Forbidden, API Key is Required!');
				$this->response($this->json($respon), 404);
			}

    }

    public function getCategoryPosts() {

    	$id = $_GET['id'];

		if($this->get_request_method() != "GET") $this->response('',406);
		$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
		$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

		$offset = ($page * $limit) - $limit;
		$count_total = $this->get_count_result("SELECT COUNT(DISTINCT nid) FROM tbl_news WHERE cat_id = '$id'");

		$query_category = "SELECT distinct cid, category_name, category_image FROM tbl_category WHERE cid = '$id' ORDER BY cid DESC";

		$query_post = "SELECT DISTINCT n.nid, 
						n.news_title, 
						n.cat_id,
						n.news_date, 
						n.news_image, 
						n.news_description,
						n.video_url,
						n.video_id, 
						n.content_type, 
									
						c.category_name, 
						COUNT(DISTINCT r.comment_id) as comments_count

						FROM tbl_news n 

						LEFT JOIN tbl_comments r ON n.nid = r.nid 
						LEFT JOIN tbl_category c ON n.cat_id = c.cid 

						WHERE c.cid = '$id' 

						GROUP BY n.nid 
						ORDER BY n.nid DESC 
								 
						LIMIT $limit OFFSET $offset";

		$category = $this->get_category_result($query_category);
		$post = $this->get_list_result($query_post);
		$count = count($post);
		$respon = array(
			'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'category' => $category, 'posts' => $post
		);
		$this->response($this->json($respon), 200);

    }

    public function getSearchResults() {

    	include "../includes/config.php";
		    $setting_qry    = "SELECT * FROM tbl_settings where id = '1'";
		    $setting_result = mysqli_query($connect, $setting_qry);
		    $settings_row   = mysqli_fetch_assoc($setting_result);
		    $api_key    = $settings_row['api_key'];

			if (isset($_GET['api_key'])) {

				$access_key_received = $_GET['api_key'];

				if ($access_key_received == $api_key) {

					$search = $_GET['search'];

					if($this->get_request_method() != "GET") $this->response('',406);
					$limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
					$page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

					$offset = ($page * $limit) - $limit;
					$count_total = $this->get_count_result("SELECT COUNT(DISTINCT n.nid) FROM tbl_news n, tbl_category c WHERE n.cat_id = c.cid AND (n.news_title LIKE '%$search%' OR n.news_description LIKE '%$search%')");

					$query = "SELECT DISTINCT n.nid, 
									n.news_title, 
									n.cat_id,
									n.news_date, 
									n.news_image, 
									n.news_description,
									n.video_url,
									n.video_id, 
									n.content_type, 
									
									c.category_name, 
									COUNT(DISTINCT r.comment_id) as comments_count

								  FROM tbl_news n 

								  LEFT JOIN tbl_comments r ON n.nid = r.nid 
								  LEFT JOIN tbl_category c ON n.cat_id = c.cid 

								  WHERE n.cat_id = c.cid AND (n.news_title LIKE '%$search%' OR n.news_description LIKE '%$search%') 

								  GROUP BY n.nid 
								  ORDER BY n.nid DESC

							LIMIT $limit OFFSET $offset";

					$post = $this->get_list_result($query);
					$count = count($post);
					$respon = array(
						'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
					);
					$this->response($this->json($respon), 200);

				} else {
					$respon = array( 'status' => 'failed', 'message' => 'Oops, API Key is Incorrect!');
					$this->response($this->json($respon), 404);
				}
			} else {
				$respon = array( 'status' => 'failed', 'message' => 'Forbidden, API Key is Required!');
				$this->response($this->json($respon), 404);
			}

    }

    public function getComments() {

			$nid = $_GET['nid'];

			if($this->get_request_method() != "GET") $this->response('',406);
			$count_total = $this->get_count_result("SELECT COUNT(DISTINCT comment_id) FROM tbl_comments c, tbl_news n WHERE n.nid = c.nid AND n.nid = '$nid'");
			
			$query = "SELECT 

			c.comment_id,
			c.user_id,
			u.display_name,
			u.imageName AS 'image',
			c.date_time,
			c.content

			FROM tbl_news n, tbl_comments c, wpe4_users u WHERE n.nid = c.nid AND c.user_id = u.id AND n.nid = '$nid' 

			ORDER BY c.comment_id DESC";
					  
			$categories = $this->get_list_result($query);
			$count = count($categories);
			$respon = array(
				'status' => 'ok', 'count' => $count, 'comments' => $categories
			);
			$this->response($this->json($respon), 200);
	}

	public function postComment() {

			if($_SERVER['REQUEST_METHOD'] == 'POST') {

			       $response = array();
			       //mendapatkan data
			       $nid = $_POST['nid'];
			       $id = $_POST['user_id'];
			       $content = $_POST['content'];
			       $date_time = $_POST['date_time'];

			       include "../includes/config.php";

			       $sql = "INSERT INTO tbl_comments (nid, user_id, content, date_time) VALUES('$nid', '$id', '$content', '$date_time')";
			       if (mysqli_query($connect, $sql)) {
			         $response["value"] = 1;
			         $response["message"] = "Comentário adicionado com sucesso";
			         echo json_encode($response);
			       } else {
			         $response["value"] = 0;
			         $response["message"] = "oops! Erro!";
			         echo json_encode($response);
			       }
			     
			     // tutup database
			     mysqli_close($connect);

			  } else {
			    $response["value"] = 0;
			    $response["message"] = "Erro ao comentar!";

			    header( 'Content-Type: application/json; charset=utf-8' );
			    echo json_encode($response);
			  }

	}

	public function updateComment() {
	        include "../includes/config.php";

		    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		        $response = array();

		        //mendapatkan data
		        $comment_id = $_POST['comment_id'];
		        $date_time  = $_POST['date_time'];
		        $content    = $_POST['content'];

		        include "../includes/config.php";

		        $sql = "UPDATE tbl_comments SET comment_id = '$comment_id', date_time = '$date_time', content = '$content' WHERE comment_id = '$comment_id'";

		        if(mysqli_query($connect, $sql)) {
		            $response["value"] = 1;
		            $response["message"] = "Comentário editado com sucesso";

		            header( 'Content-Type: application/json; charset=utf-8' );
		            echo json_encode($response);
		        } else {
		            $response["value"] = 0;
		            $response["message"] = "Erro ao editar o comentário!";

		            header( 'Content-Type: application/json; charset=utf-8' );
		            echo json_encode($response);
		        }

		        mysqli_close($connect);

		    }

	}

	public function deleteComment() {

		    include "../includes/config.php";

		    if($_SERVER['REQUEST_METHOD'] == 'POST') {
		      
		        $response = array();
		        //mendapatkan data
		        $comment_id = $_POST['comment_id'];
		        $sql = "DELETE FROM tbl_comments WHERE comment_id = '$comment_id'";

		        if(mysqli_query($connect, $sql)) {
		            $response["value"] = 1;
		            $response["message"] = "O teu comentário foi apagado com sucesso.";

		            header( 'Content-Type: application/json; charset=utf-8' );
		            echo json_encode($response);
		        } else {
		            $response["value"] = 0;
		            $response["message"] = "Erro ao apagar o comentário!";

		            header( 'Content-Type: application/json; charset=utf-8' );
		            echo json_encode($response);
		        }
		        mysqli_close($connect);

		    }

	}

		public function userRegister() {

			include "../includes/config.php";
			include "../public/register.php";
            include "../public/class-phpass.php";
            
			if(isset($_GET['user_email'])) {
	
				$qry = "SELECT * FROM wpe4_users WHERE user_email = '".$_GET['user_email']."'"; 
				$sel = mysqli_query($connect, $qry);
			
				if(mysqli_num_rows($sel) > 0) {
					$set['result'][]=array('msg' => "Email já utilizado!", 'success'=>'0');
					echo $val= str_replace('\\/', '/', json_encode($set, JSON_UNESCAPED_UNICODE));
					die();
				}
					
				$qry = "SELECT * FROM wpe4_users WHERE user_login = '".$_GET['user_login']."'"; 
				$sel = mysqli_query($connect, $qry);
			
				if(mysqli_num_rows($sel) > 0) {
					$set['result'][]=array('msg' => "Nome de Utilizador já utilizado!", 'success'=>'2');
					echo $val= str_replace('\\/', '/', json_encode($set, JSON_UNESCAPED_UNICODE));
					die();
					
				} else {
				    date_default_timezone_set(‘GMT’);
                    $date = date('Y/m/d H:i:s');
				    
				  $hasher = new PasswordHash(8, TRUE);    
			      $password = $_GET['user_pass'];
			      $hash = $hasher->HashPassword($password);
			      
		 			$data = array(
					'user_login'  => $_GET['user_login'],
					'user_email'  =>  $_GET['user_email'],
					'user_pass'  => $hash,
					'user_nicename' =>  $_GET['user_nicename'],
					'display_name' =>  $_GET['display_name'],
					'user_registered' => $date,
					'user_status'  =>  '0',
					'user_status2' => '1'
					);

					$qry = Insert('wpe4_users', $data);									 
					
					$set['result'][] = array('msg' => "Conta criada com sucesso...!", 'success'=>'1');
					echo $val= str_replace('\\/', '/', json_encode($set, JSON_UNESCAPED_UNICODE));
					die();
				}
				
			} else {
				
				 header( 'Content-Type: application/json; charset=utf-8' );
				 $json = json_encode($set);

				 echo $json;
				 exit;		 
			}

		}

		public function getUser() {

		    include "../includes/config.php";
		    
		    if(isset($_REQUEST['user_id'])) {
		         
		         $id = $_REQUEST['user_id'];
		         
		         $query = " SELECT * FROM wpe4_users WHERE id = '$user_id' ";
		         $result = mysqli_query($connect, $query);
		         
		         while ($row = mysqli_fetch_assoc($result)) {
		              $output[] = $row;
		         }
		         
		         print(json_encode($output));
		         
		         mysqli_close($connect);

		    } else {

		       $output = "Não encontrado";
		       print(json_encode($output));
		    
		    }

		}
	
	public function getUserLogin() {
		   
		    include "../includes/config.php";
		    include "../public/class-phpass.php";
		   
		    $hasher = new PasswordHash(8, TRUE);
		    $qry = "SELECT * FROM wpe4_users WHERE user_email = '".$_GET['user_email']."'";
		    $result = mysqli_query($connect, $qry);
		    $row = mysqli_fetch_assoc($result);
		    $estado = $row[user_status2];
		    $passwordinserida = $_GET[user_pass];
		    $passwordcorreta = $row[user_pass];
		    $user_id = $row[ID];
		    
		   if ($estado == 0) {
		       $set['result'][] = array('msg' => 'Conta desativada', 'success' => '2');
		   } else {
			     
			 if ($hasher->CheckPassword( $passwordinserida, $passwordcorreta)){
			     $set['result'][] = array('msg' => 'Login efetuado com sucesso!', 'user_id' => $user_id, 'user_login' => $row['display_name'], 'user_email' => $row['user_email'], 'imageName' => $row['imageName'], 'success' => '1');
			  } else {
			     $set['result'][] = array('msg' => 'Email ou Password incorrectos', 'success' => '0');
			     }
		   }
			
			header( 'Content-Type: application/json; charset=utf-8' );
			$json = json_encode($set);

			echo $json;
			exit;
			
		}
		

		public function getUserProfile() {

			include "../includes/config.php";

    			$id = $_GET['user_id'];

 	 		$qry = "SELECT * FROM wpe4_users WHERE id = '$id' ";
			$result = mysqli_query($connect, $qry);	 
			$row = mysqli_fetch_assoc($result);
			  				 
			$set['result'][] = array(
				'user_id' => $row['ID'],
				'user_login'=>$row['user_login'],
				'user_email'=>$row['user_email'],
				'user_pass'=>$row['user_pass'],
				'user_status2'=>$row['user_status2'],
				'ImageName'=>$row['imageName'],
				'success'=>'1'
				
			);

			header( 'Content-Type: application/json; charset=utf-8' );
			$json = json_encode($set);

			echo $json;
			exit;

		}

		public function updateUserProfile() {

			include "../includes/config.php";
			include "../public/register.php";
	
		 	
		    if ($_GET['user_pass']!="" AND $_GET['user_login']!="") {
		        $data = array(
		        'display_name'  =>  $_GET['user_login'],
		        'user_pass'  =>  $_GET['user_pass']
		        );
		    } elseif ($_GET['user_pass']!="") {
				$data = array(
				'user_pass'  =>  $_GET['user_pass']
				);
			} else {
				$data = array(
				'display_name'  =>  $_GET['user_login']
				);
			}
				
			$user_edit = Update('wpe4_users', $data, "WHERE id = '".$_GET['id']."'");
		 	$set['result'][] = array('msg'=>'Editado', 'success'=>'1');
					 
			header( 'Content-Type: application/json; charset=utf-8' );
			$json = json_encode($set);

			echo $json;
			exit;

		}

		public function updateUserPhoto() {

		    include "../includes/config.php";

		    // check if "image" abd "user_id" is set 
		    if(isset($_POST["ImageName"]) && isset($_POST["id"])) {

		        $data = $_POST["ImageName"];
		        $time = time();

		        $id = $_POST["ID"];
		        //$oldImage ="images/"."1_1497679518.jpg";
		        $ImageName = $id.'_'.$time.".jpg";

		        //$filePath = "images/".$ImageName;
		        $filePath = '../upload/avatar/'.$ImageName; // path of the file to store
		        echo "file : ".$filePath;
		        //echo "unlink : ".$oldImage;

		        // check if file exits
		        if (file_exists($filePath)) {
		            unlink($filePath); // delete the old file
		        } 
		        // create a new empty file
		        $myfile = fopen($filePath, "w") or die("Não foi possível abrir o ficheiro!");
		        // add data to that file
		        file_put_contents($filePath, base64_decode($data));

		        // update the Customer table with new image name.
		        $query = " UPDATE wpe4_users SET imageName = '$ImageName' WHERE id = '$id' ";
		        mysqli_query($connect, $query);

		        
		    } else {
		        echo 'not set';
		    }
		    
		    mysqli_close($connect);

		}

	public function getPrivacyPolicy() {

		include "../includes/config.php";
		
		$sql = "SELECT * FROM tbl_settings WHERE id = 1";
		$result = mysqli_query($connect, $sql);

		header( 'Content-Type: application/json; charset=utf-8' );
		print json_encode(mysqli_fetch_assoc($result));


	}
	
	public function getAboutUs() {

		include "../includes/config.php";
		
		$sql = "SELECT * FROM tbl_settings WHERE id = 1";
		$result = mysqli_query($connect, $sql);

		header( 'Content-Type: application/json; charset=utf-8' );
		print json_encode(mysqli_fetch_assoc($result));


	}
	
	public function getOurMission() {

		include "../includes/config.php";
		
		$sql = "SELECT * FROM tbl_settings WHERE id = 1";
		$result = mysqli_query($connect, $sql);

		header( 'Content-Type: application/json; charset=utf-8' );
		print json_encode(mysqli_fetch_assoc($result));

	}

    public function get_list_result($query) {
		$result = array();
		$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
		if($r->num_rows > 0) {
			while($row = $r->fetch_assoc()) {
				$result[] = $row;
			}
		}
		return $result;
	}

    public function get_count_result($query) {
		$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
		if($r->num_rows > 0) {
			$result = $r->fetch_row();
			return $result[0];
		}
		return 0;
	}

	private function get_category_result($query) {
		$result = array();
		$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
		if($r->num_rows > 0) {
			while($row = $r->fetch_assoc()) {
				$result = $row;
			}
		}
		return $result;
	}

	private function get_one($query) {
		$result = array();
		$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
		if($r->num_rows > 0) $result = $r->fetch_assoc();
		return $result;
	}
    
}

?>