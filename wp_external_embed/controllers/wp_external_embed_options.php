<?php

class wp_external_embed_options extends Wp_external_embed {

    public $menuoptions = array(
        'order' => 0,
        'page_title' => 'External Embed',
        'menu_title' => 'External Embed',        
        // 'capability' => 'manage_options', //plugin capability , optionanl    
        
        // add links to plugin option page,optional
        'pluginActionLink'=>array( 
            'action'=>'',//use action name or leave empty for index
            'title'=>'Options'
            )
        
    );

    public function index() {
        $data = array(
            "title" => 'WP External Embed'
        );


        $this->render('manage', $data);
    }

    public function update() {
        
        if(isset($_POST['update'])){
            file_put_contents($this->plugindir.'/views/template.php', trim(stripslashes($_POST['template'])));
        }       
        elseif(isset($_POST['reset'])){
            $html='<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
    <?php //print_r($post);?>
    <h2><?php echo $post->post_title; ?></h2>
    <div> <?php echo $post->post_content;?> </div>   
</body>
</html>';            
            file_put_contents($this->plugindir.'/views/template.php', $html);
        }
        wp_redirect($this->get_url($_GET['page']));
        exit;
    }


}

?>
