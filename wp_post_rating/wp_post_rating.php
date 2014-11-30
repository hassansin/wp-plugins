<?php

/*
  Plugin Name: Post Rating
  Plugin URI: mailto:rezatxe@gmail.com
  Description: Post Rating
  Author: Forhadur Reza
  Author URI: mailto:rezatxe@gmail.com
  Version: 1.0.0
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

//if (!isset($_SESSION))
//   session_start();

include_once 'lib/common.php';
//include_once 'lib/Klogger.php';
//include all widgets
//include_once 'widgets/widget.php';

Wp_post_rating::instance();

class Wp_post_rating {

    protected $slug = 'wp_post_rating';
    protected $name = 'Post Rating';
    protected $shortcode = array( 'posts-by-vote' ,'print-like-btn');
    protected $parentPage = 'options-general.php'; //leave it empty to creat new menu page
    protected $cronInterval = ''; //specify interval in seconds
    protected $defaultOptions = array(
        'version' => '1.0.0',
        'css'=>'',
        'votingMethod'=>'members', //members or all
        'content'=>'full',
        'exclude'=>array(),
        //'postType'=>array('post'),
        'category__in'=>array()
    );
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
        //$this->log = Klogger::instance( $this->plugindir, KLogger::OFF );   // KLogger::OFF
        $this->add_actions();
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
        $this->options = get_option( $this->optionName );
    }

    private function add_actions() {
        add_action( 'admin_menu', array( &$this, "add_menu" ) );
        add_action( 'admin_head', array( &$this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'public_enqueue_scripts' ) );
        add_action( 'init', array( &$this, 'ajax_init' ) );
        add_action( 'init', array( &$this, 'route' ) );
        add_action( 'widgets_init', array( &$this, 'registerWidget' ) );
        add_action( 'print-like-btn', array( &$this, 'print_like_btn' ) );
        add_action( 'wp_footer',array(&$this,'printbtncss'));
        add_action( 'wp_insert_post', array(&$this,'addRatingMetaKey'));

        if ( $this->cronInterval ) {
            add_filter( 'cron_schedules', array( $this, 'create_custom_schedule_event' ) );
            add_action( $this->slug . '_cron', get_class() . '::cron_handler' );
        }

        if ( isset( $this->shortcode ) ) {
            foreach ( $this->shortcode as $code )
                add_shortcode( $code, array( $this, 'shortcode_hook' ) ); //register all shortcodes
        }
    }
    public function addRatingMetaKey($post_id){                    
        add_post_meta($post_id, 'votes_count',0, true);            
        add_post_meta($post_id, 'user_id','', true);
        add_post_meta($post_id, 'voted_ip','', true);
        return true;

    }
    public function printbtncss(){
        if(defined('btncssprinted')){
            echo '<style>';
            echo $this->get('css');
            echo '</style>';
            wp_enqueue_script( 'wp_post_ratings', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), '1.0', true );
        }
    }

    public function print_like_btn($return = false) {
        if(!defined('btncssprinted')){
            define('btncssprinted', true);
        }
            
        global $post;
        $user_id = get_current_user_id();
        $post_id = $post->ID;

        $meta_count = (int) get_post_meta( $post_id, "votes_count", true );
        $isLiked=false;

        if($this->get('votingMethod')=='members'){
            $meta_user_id = array_filter( (array) get_post_meta( $post_id, "user_id", true ) );
            if ( in_array( $user_id, $meta_user_id ) ) {
            $isLiked = true;
            }
        }
        else{
            $ip = $_SERVER['REMOTE_ADDR'];              
            $meta_user_ip = array_filter( (array) get_post_meta( $post_id, "voted_ip", true ) );            
             if(in_array($ip, array_keys($meta_user_ip)))  
                {  
                    $time = $meta_user_ip[$ip];  
                    /*
                    $now = time();                        
                    // Compare between current time and vote time  
                    if(round(($now - $time) / 60) > $timebeforerevote)  
                        return false;  
                    */      
                    $isLiked = true; 
                }  
                  
        }
        
        
        



        if(!$return)
            $this->render( 'rating_button', array( 'post'=>$post, 'isLiked'=>$isLiked, 'count'=>$meta_count ));
        else{
            $html = $this->render( 'rating_button', array( 'post'=>$post, 'isLiked'=>$isLiked, 'count'=>$meta_count ),false);
            return $html;
        }
        
    }
    public function plugin_action_links( $links, $file ) {

        if ( basename( $file ) == basename( __FILE__ ) ) {
            //$this->log->logInfo('PluginActionLinks',$this->pluginActionLinks);
            foreach ( $this->pluginActionLinks as $link )
                $links[] = '<a href="'.$this->get_url( $link['page'], $link['action'] ).'">'.$link['title'].'</a>';
        }
        return $links;

    }
    public function registerWidget() {

        $dir = @opendir( $this->plugindir . '/widgets/' );
        if ( $dir ) {
            $widgets = array();
            while ( ( $entry = @readdir( $dir ) ) !== false ) {
                if ( strrchr( $entry, '.' ) === '.php' ) {
                    require_once $this->plugindir . '/widgets/' . $entry;
                    $class = substr( $entry, 0, -4 ); // remove .php and get class name
                    register_widget( $class );
                }
            }
        }


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

    public function admin_enqueue_scripts() {
        global $hook_suffix; // check this variable first and then use accordingly

        if ( $hook_suffix == 'edit-comments.php'
            || $hook_suffix == 'comment.php'
            || $hook_suffix == 'post.php'
            || $hook_suffix == 'plugins_page_akismet-key-config'
        ) {
            wp_enqueue_script( 'jquery' );

        }

    }

    //handles all ajax calls
    public function ajax_handler() {
        //is there any security breach??
        $controller = $_GET['controller'];
        $method = $_GET['method'];
        if ( file_exists( $this->plugindir . '/controllers/' . $controller . '.php' ) ) {
            require_once $this->plugindir . '/controllers/' . $controller . '.php';
            $class = ucfirst( $controller );
            $instance = new $class();
            $instance->$method();
        }
        exit;
    }

    public function create_custom_schedule_event( $schedules ) {
        $schedules[$this->slug . '_scheduler'] = array(
            'interval' => $this->cronInterval,
            'display' => __( $this->name . ' Scheduler' )
        );
        return $schedules;
    }


    public function public_enqueue_scripts() {
        //wp_enqueue_script( 'jquery' );
        //wp_register_script('jquery-ui', 'http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'jquery');
        //wp_enqueue_script('jquery-ui');
        // wp_enqueue_script( 'wp_post_ratings', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), '1.0', true );
        //wp_enqueue_style($this->slug . '-style', $this->pluginurl . 'css/' . 'style.css');
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
        $wpdb->query("insert into wp_postmeta (post_id, meta_key, meta_value) 
                    select ID, 'user_id', '' from wp_posts");
        $wpdb->query("insert into wp_postmeta (post_id, meta_key, meta_value) 
                    select ID, 'votes_count', '' from wp_posts");
        $wpdb->query("insert into wp_postmeta (post_id, meta_key, meta_value) 
                    select ID, 'voted_ip', '' from wp_posts");
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


    public function cron_handler() {

    }

    public function shortcode_hook( $attr, $content=null, $code ) {
        if($code=='print-like-btn'){
            return $content. $this->print_like_btn(true);

        }
        $paged = get_query_var('paged');
        $ipp = isset( $attr['ipp'] )? (int) $attr['ipp']:10;
        
        //get posts by votes
        $args = array(
            'meta_key'=>'votes_count',
            'orderby'=>'meta_value_num',
            'order'=>'DESC',
            'posts_per_page'=>$ipp,
            'paged'=>$paged,
            'post__not_in'=> $this->get('exclude'),
            // 'post_type'=>$this->get('postType'),            
            'category__in'=>$this->get('category__in')
            );
        $query = new WP_query($args);        
        $html = $this->render( 'popular-posts', array( 'query'=>$query ), false );
        wp_reset_query();
        return $content.  $html;
    }


    //embed ajax object in admin and public pages
    public function ajax_init() {        

        $jsObject = array( 'ajaxurl' => admin_url( 'admin-ajax.php' ).'?action='.$this->slug );
        wp_enqueue_script('jquery');
        wp_localize_script( 'jquery', $this->slug, $jsObject );
        // this hook is fired if the current viewer is not logged in
        add_action( 'wp_ajax_nopriv_'.$this->slug, array( &$this, 'ajax_handler' ) );
        add_action( 'wp_ajax_'.$this->slug, array( &$this, 'ajax_handler' ) );
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
