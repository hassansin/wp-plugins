 
<?php
$path  = ''; // It should be end with a trailing slash    
if (!defined('WP_LOAD_PATH')) {
	$classic_root = dirname(dirname(dirname(dirname(dirname(__FILE__))))).DIRECTORY_SEPARATOR;	
	if (file_exists($classic_root.'wp-load.php') ) {
		define('WP_LOAD_PATH', $classic_root);
	} else {
		if (file_exists($path.'wp-load.php')) {
			define('WP_LOAD_PATH', $path);
		} else {
			exit("Could not find wp-load.php");
		}
	}
}

//Load wp-load.php
require_once(WP_LOAD_PATH.'wp-load.php');

class Yumpu_embed_pdf_editorWindow extends Yumpu_embed_pdf{
		
	function showWindow(){
		$tokenAdded = $this->get('accessTokenActive');
		if(empty($tokenAdded))
		{
			echo "Enter your Yumpu.com API Token. <a target='_blank' href='".$this->get_url('yumpu_embed_pdf_options')."'>Click here</a> to Enter";

		}
		else{
			$data=array();
			$this->render('window',$data);
		}
	}

}

$inst = new Yumpu_embed_pdf_editorWindow();
$inst->showWindow();
?>
