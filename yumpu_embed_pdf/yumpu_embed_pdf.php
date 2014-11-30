<?php

//Plugin Author: Forhadur Reza<rezatxe@gmail.com>  

/*
  Plugin Name: Yumpu PDF Embed
  Description:  Yumpu is a Free PDF to E-Paper site.<br/>The Service allows you to upload a PDF, the API then returns an i-Frame that shall be embedded.<br/>The wordpress Plugin shall help the user to upload and embed a PDF faster.
  Author: Yumpu Development Team  
  Version: 1.0.0
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

include_once 'lib/common.php';
require_once 'lib/API.Yumpu.php';
//require_once 'lib/Klogger.php';

Yumpu_embed_pdf::instance();

class Yumpu_embed_pdf {

    protected $slug = 'yumpu_embed_pdf';
    protected $name = 'Yumpu Embed PDF';
    protected $shortcode = array( 'Yumpu-Embed' );
    protected $parentPage = 'options-general.php'; //leave it empty to creat new menu page
    protected $cronInterval = ''; //specify interval in seconds
    protected $defaultOptions = array(
        'version' => '1.0.0',
        'accessTokenPrimary'=>'2b5fb54f42e66c5d0edc08717cba7f9f',
        'accessTokenActive'=>''
    );
    protected $optionName;
    protected $options = array();
    protected $basename;
    protected $plugindir;
    protected $pluginurl;
    public static $_inst;

    public function __construct() {

        $this->plugindir = realpath( dirname( __FILE__ ) );
        $this->pluginurl = plugin_dir_url( __FILE__ );
        $this->basename = plugin_basename( __FILE__ );
        $this->optionName = $this->slug . '-options'; //plugin options
        //$this->log = Klogger::instance( $this->plugindir );   // KLogger::OFF
        $this->add_actions();
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
        $this->options = get_option( $this->optionName );
    }

    private function add_actions() {
        add_action( 'admin_menu', array( &$this, "add_menu" ) );
        add_action( 'admin_head', array( &$this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'public_enqueue_scripts' ) );
        add_action( 'admin_init', array( &$this, 'ajax_init' ) );
        add_action( 'init', array( &$this, 'route' ) );
        //add_action( 'widgets_init', array( &$this, 'registerWidget' ) );
        add_action( 'admin_init', array( &$this, 'tinyMCE_init' ) );


        if ( $this->cronInterval ) {
            add_filter( 'cron_schedules', array( $this, 'create_custom_schedule_event' ) );
            add_action( $this->slug . '_cron', get_class() . '::cron_handler' );
        }

        if ( isset( $this->shortcode ) ) {
            foreach ( $this->shortcode as $code )
                add_shortcode( $code, array( $this, 'shortcode_hook' ) ); //register all shortcodes
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
    public function change_tinymce_version( $version ) {
        return ++$version;
    }

    public function registerButton( $buttons ) {
        array_push( $buttons, "|", "yumpu_embed_pdf" );
        return $buttons;
    }
    public function add_tinymce_plugin( $plugin_array ) {
        $plugin_array['yumpu_embed_pdf'] = $this->pluginurl.'js/editor_plugin.js';
        return $plugin_array;
    }

    public function tinyMCE_init() {
        if ( get_user_option( 'rich_editing' ) == 'true' &&  current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
            // add the button for wp25 in a new way
            add_filter( "mce_external_plugins", array( &$this, "add_tinymce_plugin" ), 5 );
            add_filter( 'mce_buttons', array( &$this, 'registerButton' ), 5 );
        }
    }
    public function admin_notice() {
        $accessToken=$this->get( 'accessTokenActive' );
        if ( empty( $accessToken ) ) {
            echo '<div class="updated"><p>Add your Yumpu.com API Key, get it for free at <a target="_blank" href="http://yumpu.com">Yumpu.com</a>  or Create your free account from <a href="'.$this->get_url( 'yumpu_embed_pdf_options' ).'">here</a></p></div>';
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
            add_action( 'admin_notices', array( &$this, 'admin_notice' ) );

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

        if ( isset( $_GET['page'] ) && $_GET['page']=='yumpu_embed_pdf_options' ) {
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
        wp_enqueue_script( 'jquery' );
        //wp_register_script('jquery-ui', 'http://code.jquery.com/ui/1.10.0/jquery-ui.js', 'jquery');
        //wp_enqueue_script('jquery-ui');
        // wp_enqueue_script('my-script', plugins_url('my-script.js', __FILE__), array('jquery'), '1.0', true);
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

        $table = $wpdb->prefix . 'yep_docs';
        $sql = "CREATE TABLE $table (
          id INTEGER NOT NULL AUTO_INCREMENT,
          response_id VARCHAR(50) NOT NULL,
          response_url VARCHAR(100) NOT NULL,
          status VARCHAR(3) NOT NULL,
          width SMALLINT  default 512 NOT NULL,
          height SMALLINT  default 384 NOT NULL,
          input_url  VARCHAR(100) NOT NULL,
          title VARCHAR(50) DEFAULT '' NOT NULL,

          document_id INTEGER DEFAULT 0 NOT NULL,
          document_url VARCHAR(100) DEFAULT '' NOT NULL,
          embed_code TEXT DEFAULT '',

          created INTEGER NOT NULL,
          UNIQUE KEY id (id)
          );";
        dbDelta( $sql );
    }

    public function deactivate_plugin() {
        //delete_option($this->optionName);
    }


    public function cron_handler() {

    }

    public function shortcode_hook( $attr, $content=null , $code ) {

        if ( $code=='Yumpu-Embed' ) {
            $id=$attr['documentid'];
            $width=isset( $attr['width'] )? (int) $attr['width']: 512;
            $height = isset( $attr['height'] )? (int) $attr['height'] : 384;
            global $wpdb;
            $table = $wpdb->prefix . 'yep_docs';
            $row= $wpdb->get_row( "SELECT * FROM $table WHERE id=$id" );

            if(count($row)!=1){
                return $content.'<div style="width:'.$width.';height:'.$height.'"><p>Document Not Found</p></div>';
            }
            elseif ( count($row)==1 && empty($row->embed_code) ) {

                $response_url = $row->response_url;
                $response = wp_remote_get($response_url);
                if(is_wp_error( $response ) || 200 != $response['response']['code']){
                    return $content.'<div>Document in progress</div>';
                }
                $response = json_decode($response['body']);
                if( isset($response->document->id) && strstr(@$response->document->id,'(not set)' ))
                    return $content.'<div style="width:'.$width.';height:'.$height.'"><p>Your document is in progress</p></div>';
                $row=array(
                    'document_id'=>$response->document->id,
                    'document_url'=>$response->document->url,
                    'embed_code'=>$response->document->embed_code
                    );
                $wpdb->update($table, $row, array('id'=>$id));
                $embed_code = $response->document->embed_code;
                //replace height and width
                $embed_code= preg_replace( '/width:(.*?)px/i', "width:".$width."px", $embed_code );
                $embed_code= preg_replace( '/height:(.*?)px/i', "height:".$height."px", $embed_code );

                $embed_code= preg_replace( '/width="(.*?)px"/i', "width=\"".$width."px\"", $embed_code );
                $embed_code= preg_replace( '/height="(.*?)px"/i', "height=\"".$height."px\"", $embed_code );
                return $content.$embed_code;
            }
            else {
                $embed_code = $row->embed_code;
                //replace height and width
                $embed_code= preg_replace( '/width:(.*?)px/i', "width:".$width."px", $embed_code );
                $embed_code= preg_replace( '/height:(.*?)px/i', "height:".$height."px", $embed_code );

                $embed_code= preg_replace( '/width="(.*?)px"/i', "width=\"".$width."px\"", $embed_code );
                $embed_code= preg_replace( '/height="(.*?)px"/i', "height=\"".$height."px\"", $embed_code );
                return $content.$embed_code;
            }
        }
    }


    //embed ajax object in admin and public pages
    public function ajax_init() {

        $jsObject = array( 'ajaxurl' => admin_url( 'admin-ajax.php' ).'?action='.$this->slug );
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
