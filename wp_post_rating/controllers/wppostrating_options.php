<?php

class wppostrating_options extends Wp_post_rating {

    public $menuoptions = array(
        'order' => 0,
        'page_title' => 'Settings',
        'menu_title' => 'Post Ratings',
        //'capability' => 'manage_options', //plugin capability , optionanl

        // add links to plugin option page,optional
        'pluginActionLink'=>array(
            'action'=>'', //use action name or leave empty for index
            'title'=>'Options'
        )

    );

    public function index() {
        $data = array(
            "title" => 'Settings'
        );


        $this->render( 'manage', $data );
    }

    public function update() {


        $this->save( 'category__in',(array) $_POST['category__in']);
        //$this->save( 'postType',(array) $_POST['postType']);
        $this->save( 'exclude', array_filter(explode(',',$_POST['exclude'])));
        $this->save( 'css', $_POST['css'] );
        $this->save( 'content', $_POST['content'] );
        $this->save( 'votingMethod', $_POST['votingMethod'] );
        wp_redirect( $this->get_url( 'wppostrating_options' ) );
        exit;
    }


    public function ratePost() {

        // Check for nonce security
        if ( isset( $_GET['id'] ) ) {

            $post_id = (int) $_GET['id'];
            $meta_count = (int) get_post_meta( $post_id, "votes_count", true );
            $incre =false;

            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
                $meta_user_id = array_filter( (array) get_post_meta( $post_id, "user_id", true ) );
                //has not liked yet
                if ( !in_array( $user_id, $meta_user_id ) ) {
                    $meta_user_id[]=$user_id;
                    update_post_meta( $post_id, "user_id", $meta_user_id );
                    $incre = true;
                }
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            $meta_user_ip = array_filter( (array) get_post_meta( $post_id, "voted_ip", true ) );
            if ( !in_array( $ip, $meta_user_ip ) ) {
                $meta_user_ip[$ip]=time();
                update_post_meta( $post_id, "voted_ip", $meta_user_ip );
                $incre= true;
            }
            if ( $incre )
                update_post_meta( $post_id, "votes_count", ++$meta_count );

            // Get voters'IPs for the current post
            echo json_encode( array( 'count'=>$meta_count, 'stat'=>'ok' ) );
        }
        exit;
    }

}

?>
