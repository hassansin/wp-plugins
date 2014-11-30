<?php

class wp_comment_moderation_options extends Wp_comment_moderation {

    public $menuoptions = array(
        'order' => 0,
        'page_title' => 'Comment Moderation',
        'menu_title' => 'Comment Moderation',        
        // 'capability' => 'manage_options', //plugin capability , optionanl    
        
        // add links to plugin option page,optional
        'pluginActionLink'=>array( 
            'action'=>'',//use action name or leave empty for index
            'title'=>'Options'
            )
        
    );

    public function index() {
        $data = array(
            "title" => 'Settings'
        );
        $this->render('manage', $data);
    }

    public function update() {
        $this->save('data',array_keys($_POST['data']));
        $this->save('var',$_POST['var']);
        $this->save('fields',$_POST['fields']);
        /*if(isset($_POST['api-url']))
            $this->save('api-url',$_POST['api-url']);*/

        wp_redirect($this->get_url($_GET['page']));
        exit;
    }

}

?>
