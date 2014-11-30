<?php

class yumpu_embed_pdf_options extends Yumpu_embed_pdf {

    public $menuoptions = array(
        'order' => 0,
        'page_title' => 'Yumpu PDF Settings',
        'menu_title' => 'Yumpu PDF Settings',
        // 'capability' => 'manage_options', //plugin capability , optionanl

        // add links to plugin option page,optional
        'pluginActionLink'=>array(
            'action'=>'', //use action name or leave empty for index
            'title'=>'Settings'
        )

    );

    public function index() {
        $data = array(
            "title" => 'Settings'
        );
        if(isset($_COOKIE['yaa']) && $_COOKIE['yaa']=='false')
            $data['msg']="<div  class=\"updated msgbh\"><p>IMPORTANT: Please check your E-Mail and activate your User Account</p></div>";
        $this->render( 'settings', $data );
    }

    public function update() {

        $_SESSION['msg']="";
        wp_redirect( $this->get_url( 'shoprotator_distributors' ) );
        exit;
    }

    public function delete() {
        global $wpdb;
        $table = $wpdb->prefix . 'shopr_distributors';
        if ( isset( $_GET['id'] ) ) {
            $id = $_GET['id'];
            $wpdb->query( "DELETE FROM $table WHERE id='$id'" );
        }
        $this->index();
    }

    function ajaxHandler() {
        if ( isset( $_POST['APIMethod'] ) ) {
            if ( $_POST['APIMethod']=='check' ) {
                $accssToken=trim( $_POST['accessToken'] );
                $api= new API_Yumpu( $accssToken );
                $api->testAPI();
                $response = $api->getResponse();
                var_dump($response);
                if ( $response->status=='400' && strstr( $response->message, 'Input data not valid' ) ) {
                    $this->save( 'accessTokenActive', $accssToken );
                    setcookie('yaa','true',time()-3600,COOKIEPATH,COOKIE_DOMAIN);//unset yaa(yumpu account activated)
                    echo 'OK';
                }
                else
                    echo 'INVALID';
            }
            elseif ( $_POST['APIMethod']=='createUser' ) {
                $username=trim( $_POST['username'] );
                $email = trim( $_POST['email'] );
                $fname = trim( $_POST['fname'] );
                $lname = trim( $_POST['lname'] );
                $gender = $_POST['gender']=='M'?1:2;
                $api= new API_Yumpu( $this->get( 'accessTokenPrimary' ) );
                $api->createUser( $username, $email, $fname, $lname, $gender );
                $response = $api->getResponse();
                if ( $response->status=='400' ){                                        
                    echo json_encode( array( 'status'=>'failed', 'message'=>$response->message ) );
                }                    
                elseif ( $response->status=='200' ) {
                    setcookie( 'yaa','false',time()+30*24*3600,COOKIEPATH,COOKIE_DOMAIN); //set cookie for sticky notice untill account is activated
                    $this->save( 'accessTokenActive', $response->user_data->access_token );
                    echo json_encode( array( 'status'=>'ok', 'message'=>$response->message, 'token'=>$response->user_data->access_token ) );
                }
                else
                    echo json_encode( array( 'status'=>'failed', 'message'=>'Unknown Error' ) );

            }
        }

        exit;
    }
    function pdfUploader() {
        //echo json_encode(array('status'=>'200','id'=>3));exit;

        $upload_dir = wp_upload_dir();        
        $targetFolder = $upload_dir['basedir']. '/yumpuPdf/'; // Relative to the root
        //check if upload dir exists or create it
        if(!is_dir($targetFolder))
            if(!mkdir($targetFolder))
            {
                echo json_encode(array('status'=>'400','message'=>'Unable to create Upload Folder'));
                exit;
            }                

        $verifyToken = md5( 'mhk63K' . $_POST['timestamp'] );        

        if ( !empty( $_FILES ) && $_POST['token'] == $verifyToken ) {
            $tempFile = $_FILES['Filedata']['tmp_name'];
            $targetPath = $targetFolder;
            $targetFile = $targetFolder. $_FILES['Filedata']['name'];

            // Validate the file type
            $fileTypes = array( 'pdf' ); // File extensions
            $fileParts = pathinfo( $_FILES['Filedata']['name'] );
            $title = trim($_POST['title']);

            if ( !in_array( $fileParts['extension'], $fileTypes ) ) {                
                echo json_encode(array('status'=>'400','message'=>'Invalid File Type'));
                exit;
            }
            elseif(empty($title)){
                echo json_encode(array('status'=>'400','message'=>'Empty Title Field'));
                exit;
            }
            elseif(strlen($title)<5){
                echo json_encode(array('status'=>'400','message'=>'Document title must be at least 5 characters long'));
                exit;
            }
            else {
                move_uploaded_file( $tempFile, $targetFile );
                $url = $upload_dir['baseurl'].'/yumpuPdf/'.$_FILES['Filedata']['name'];                                
                // $url='http://tv-dev.rs.af.cm/wp-content/uploads/yumpuPdf/80539_DS.pdf';                
                $api = new API_Yumpu( $this->get( 'accessTokenActive' ) );
                $api->createMegazine($url,$title);
                $response = $api->getResponse();
                // if response is not valid  object
                if(!isset($response->response_id)){
                    echo json_encode(array('status'=>'400','message'=>'Unknown Error'));                    
                    exit;
                }                
                global $wpdb;
                $data=array(
                    'response_id'=>$response->response_id,
                    'response_url'=>$response->response_url,
                    'status'=>$response->status,
                    'input_url'=>$url,
                    'title'=>$title,
                    'created'=>time()
                    );
                $table = $wpdb->prefix . 'yep_docs';
                $wpdb->insert($table, $data);
                $documentid = $wpdb->insert_id;

                if($response->status=='400'){
                    echo json_encode(array('status'=>'400','message'=>$response->message,'id'=>$documentid));                    
                }
                elseif($response->status=='200'){

                    echo json_encode(array('status'=>'200','id'=>$documentid));                       
                }                                        
            }
        }
    }
    function getEmbedCode($id){
        global $wpdb;        
        $table = $wpdb->prefix . 'yep_docs';
        $row= $wpdb->get_row("SELECT * FROM $table WHERE id=$id");
        if(count($row)!=1)
            return false;
        $response_url= $row->response_url;
        $response = wp_remote_get($response_url);
        if(is_wp_error( $response ) || 200 != $response['response']['code']){
            return false;
        }
        $response = json_decode($response['body']);
        if( isset($response->document->id) && strstr(@$response->document->id,'(not set)' ))
            return false;
        $row=array(
            'document_id'=>$response->document->id,
            'document_url'=>$response->document->url,
            'embed_code'=>$response->document->embed_code
            );
        $wpdb->update($table, $row, array('id'=>$id));
        $row= $wpdb->get_row("SELECT * FROM $table WHERE id=$id");
        return $row;
    }

    function ajaxGetEmbedCode(){
        
        $id=(int)$_GET['id'];
        $row = $this->getEmbedCode($id);
        if($row)
            echo json_encode(array('status'=>'ok','code'=>$row->embed_code,'url'=>$row->document_url));
        else
            echo json_encode(array('status'=>'failed'));    
    }
}

?>
