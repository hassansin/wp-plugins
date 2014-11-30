<?php

/*
  Plugin Name: External Comment Moderation
  Plugin URI: mailto:rezatxe@gmail.com
  Description: External Comment Moderation
  Author: Forhadur Reza
  Author URI: mailto:rezatxe@gmail.com
  Version: 1.0.0
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

Wp_comment_moderation::instance();

class Wp_comment_moderation{

    protected $slug = 'wp_comment_moderation';
    protected $name = 'Comment Moderation';    
    protected $parentPage = 'options-general.php'; //leave it empty to creat new menu page    
    protected $defaultOptions = array(
        'version' => '1.0.0',        
        'data'=>array(),
        'var'=>array(),
        'fields'=>array()
    );
    protected $api_url="http://google.com/";
    protected $optionName;
    protected $options = array();
    protected $basename;
    protected $plugindir;
    protected $pluginurl;
    private static $_inst;

    public function __construct() {

        $this->plugindir = realpath( dirname( __FILE__ ) );
        $this->pluginurl = plugin_dir_url( __FILE__ );
        $this->basename = plugin_basename( __FILE__ );
        $this->optionName = $this->slug . '-options'; //plugin options        
        $this->add_actions();
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
        $this->options = get_option( $this->optionName );
    }

    private function add_actions() {
        add_action( 'admin_menu', array( &$this, "add_menu" ) );        
        add_action( 'init', array( &$this, 'route' ) );    
        add_action( 'init', array( &$this, 'api_command' ) );    
        add_filter( 'pre_comment_approved' , array(&$this,'pre_comment_approved') , '99', 2 );
        add_action('wp_insert_comment',array(&$this,'wp_insert_comment') ,99,2);

    }
    function api_command(){
        $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);        
        if(strstr($request_uri,'commentaction') && isset($_GET['commentid'],$_GET['action']) ){
            $commentid=(int) $_GET['commentid'];
            $action = $_GET['action'];
            $response = array();
            if($action=='approve'){
                $commentarr = get_comment($commentid, ARRAY_A);                
                $commentarr['comment_approved'] = 1; 
                wp_update_comment($commentarr);                
                $response['comment']=$commentarr;
            }
            elseif($action=='unapprove'){
                $commentarr = get_comment($commentid, ARRAY_A);                
                $commentarr['comment_approved'] = 0; 
                wp_update_comment($commentarr);                
                $response['comment']=$commentarr;
            }
            elseif($action=='spam'){
                $commentarr = get_comment($commentid, ARRAY_A);                
                $commentarr['comment_approved'] = 'spam'; 
                wp_update_comment($commentarr);                
                $response['comment']=$commentarr;
            }

            echo json_encode($response);
            exit;
        }
        
    }

    //send comment data to external api
    function wp_insert_comment($comment_id, $comment_object){
                
        $datatosend = $this->get('data');
        $var = $this->get('var');
        $fields = $this->get('fields');

        $data= array('id'=>$comment_id);//must send comment id
        //comment fields
        foreach ($datatosend as $value) {
            $data[$var[$value]]=$comment_object->$value;
        }

        //additional fields
        foreach ($fields as $value) {            
            $data[$value[0]]=$value[1];
        }
        
        $url = $this->api_url.'?'. http_build_query($data);                
        $this->curl_get($url);
    }

    //used to disapprove all new comments
    function pre_comment_approved($approved,$commentdata){
               return 0;//disapproved
    }
    private function curl_get($url,$refer="",$https=false){
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_REFERER, $refer);        
        curl_setopt($process, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31");        
        curl_setopt($process, CURLOPT_TIMEOUT, 30);                
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);

        if ($https) {
            curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
            //curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
        }   
        
        $return = curl_exec($process);

        //$this->header = curl_getinfo($process, CURLINFO_EFFECTIVE_URL);          
        //$this->content = $return;

        /*if ($error = curl_error($process)) // Check for cURL errors
            $this->curl_error = $error;*/

        curl_close($process);
        return $return;
    }

    public function plugin_action_links( $links, $file ) {

        if ( basename( $file ) == basename( __FILE__ ) ) {
            //$this->log->logInfo('PluginActionLinks',$this->pluginActionLinks);
            foreach ( $this->pluginActionLinks as $link )
                $links[] = '<a href="'.$this->get_url( $link['page'], $link['action'] ).'">'.$link['title'].'</a>';
        }
        return $links;

    }    

    public function add_menu() {
        $parent_slug = $this->parentPage;
        //add separate menu option for all files in controllers folder
        $dir = @opendir( $this->plugindir . '/controllers/' );

        if ( $dir ) {
            $menus = array();
            $this->pluginActionLinks=array();
            while ( ( $entry = @readdir( $dir ) ) !== false ) {
                if ( strrchr( $entry, '.' ) === '.php' ) {
                    require_once $this->plugindir . '/controllers/' . $entry;
                    $class = substr( $entry, 0, -4 ); // remove .php and get class name
                    $instance = new $class();
                    $return = $instance->menuoptions; //
                    //add class name as slug i.e admin.php?page={$class} - for routing purpose
                    $return['slug'] = $class;
                    $return['capability'] = isset( $return['capability'] )?$return['capability']:'manage_options';
                    //check if plugin action link is set to true
                    if ( isset( $return['pluginActionLink'] ) && is_array( $return['pluginActionLink'] ) )
                        $this->pluginActionLinks[]= array_merge( $return['pluginActionLink'], array( 'page'=>$class ) );
                    $menus[] = $return;
                }
            }

            usort( $menus, array( $this, 'sort_menus' ) ); //sort menu options by 'order' key

            add_action( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );

            if ( !$parent_slug ) {
                $parent = array_shift( $menus ); //remove first element. It will be used as parent page
                add_menu_page( $this->name, $parent['menu_title'], 'manage_options', $parent['slug'] );
                add_submenu_page( $parent['slug'], $parent['page_title'], $parent['menu_title'], $parent['capability'], $parent['slug'], array( $this, 'menuCallback' ) );
                $parent_slug = $parent['slug'];
            }
            //add submenu page for the rest
            foreach ( $menus as $menu )
                add_submenu_page( $parent_slug, $menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['slug'], array( $this, 'menuCallback' ) );
            @closedir( $dir );
        }
    }

    static function instance() {

        if ( !isset( self::$_inst ) ) {
            $className = __CLASS__;
            self::$_inst = new $className;
        }
        return self::$_inst;
    }

    public function activate_plugin() {

        if ( isset( $this->cronInterval ) )
            wp_schedule_event( time(), $this->slug . '_scheduler', $this->slug . '_cron' );

        if ( ( $old_option = get_option( $this->optionName ) ) ) { //if option already exists
            //merge new options and keep the values of old options.
            $merged_option = array_merge( $this->defaultOptions, $old_option );
            update_option( $this->optionName, $merged_option );
        }
        else {
            add_option( $this->optionName, $this->defaultOptions );
        }


        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;
        /*
          $table = $wpdb->prefix . 'calculator_builder';
          $sql = "CREATE TABLE $table (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          name VARCHAR(50) DEFAULT '' NOT NULL,
          html TEXT NOT NULL,
          created TIMESTAMP  DEFAULT CURRENT_TIMESTAMP  NOT NULL,
          UNIQUE KEY id (id)
          );";
          dbDelta($sql);
         *
         */
    }

    public function deactivate_plugin() {
        //delete_option($this->optionName);
    }

    //process post request or any other processing required to do during initialization
    public function route() {
        if ( !isset( $_POST[$this->slug . '_controller'] ) || !isset( $_POST[$this->slug . '_method'] ) )
            return;
        $controller = $_POST[$this->slug . '_controller']; //controller name
        $action = $_POST[$this->slug . '_method'];

        if ( file_exists( $this->plugindir . '/controllers/' . $controller . '.php' ) ) {
            require_once $this->plugindir . '/controllers/' . $controller . '.php';
            $class = ucfirst( $controller );
            $controller = new $class();
            if ( method_exists( $controller, $action ) === false ) {
                die( 'Action doesn\'t exists' );
            }
            $controller->$action();
        }
        else {
            die( 'Controller doesn\'t exists' );
        }
    }

    //sort menu pages
    public function sort_menus( $a, $b ) {

        return $a['order'] - $b['order'];
    }

    //generates admin menu pages
    public function menuCallback( $page='', $action='' ) {
        if ( !$page )
            $page = $_GET['page']; //controller name
        if ( !$action )
            $action = isset( $_GET['action'] ) ? $_GET['action'] : 'index'; //method name

        if ( file_exists( $this->plugindir . '/controllers/' . $page . '.php' ) ) {
            require_once $this->plugindir . '/controllers/' . $page . '.php';
            $class = ucfirst( $page );
            $controller = new $class();
            $controller->$action();
        }
        else {
            echo _e( 'controller not found' );
        }
    }

    //template parser  from view folder
    protected function render( $template, $data=array(), $echo=true ) {

        $file = $this->plugindir . '/views/' . $template . '.php';
        if ( !file_exists( $file ) ) {
            if ( $echo ) {
                echo 'View file doesn\'t exists';
                return;
            }
            else
                return 'View file doesn\'t exists';
        }
        extract( $data );
        if ( $echo ) {
            include $file;
        }
        else {
            ob_start();
            include $file;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }

    //returns url for admin page
    protected function get_url( $page='', $action='' ) {
        if ( !empty( $this->parentPage ) )
            $url = admin_url() . $this->parentPage;
        else
            $url = admin_url() . 'admin.php';

        if ( $page )
            $url .= '?page=' . $page;
        if ( $action )
            $url .='&action=' . $action;
        return $url;
    }

    //save a single option in options array save('version','1.0.1');
    protected function save( $optionName, $optionval ) {
        if ( key_exists( $optionName, $this->options ) ) {
            $this->options[$optionName] = $optionval;
            update_option( $this->optionName, $this->options );
            return true;
        }
        return false;
    }

    //get a sigle option value eg get('version');
    protected function get( $optionName ) {
        if ( isset( $this->options[$optionName] ) ) {
            return $this->options[$optionName];
        }
        return false;
    }

    //enable and display errors


}

?>
