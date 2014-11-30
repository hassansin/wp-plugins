<div class="wrap">
    <div id="icon-options-general" class="icon32"><br>
    </div>
    <h2><?php echo $title ?> </h2>
    <script>    
    </script>
    <style>
    </style>    
    <form action="" method="post">
        <input type="hidden" name="<?php echo $this->slug . '_controller' ?>" value="<?php echo $_GET['page'] ?>"/>
        <input type="hidden" name="<?php echo $this->slug . '_method' ?>" value="update"/>
        <table class="form-table">
        <!--     
            <tr>
                <th scope="row">External API URL:</th>
                <td><input type="text" name="api-url" value="<?php echo $this->get('api-url') ?>" class="regular-text code" /></td>
            </tr> -->            
            <tr>
                <th scope="row">Items to Send: </th>
                <td>
                    <?php 
                    $var = $this->get('var');
                    $defaul_var = array(                        
                        'comment_content'=>'comment_content',
                        'comment_agent'=>'comment_agent',
                        'comment_post_ID'=>'comment_post_ID',
                        'user_id'=>'user_id',
                        'comment_author'=>'comment_author',
                        'comment_author_url'=>'comment_author_url',
                        'comment_author_IP'=>'comment_author_IP',                        
                        'comment_author_email'=>'comment_author_email',
                        );    
                    $var= array_merge($defaul_var,(array) $var);
                    $data = $this->get('data');
                    $comment_data = array('comment_content','comment_agent','comment_post_ID','comment_author','comment_author_url','comment_author_IP','comment_author_email','user_id');
                    foreach ($comment_data as $value) {
                        $checked = in_array($value,(array) $data)?'checked':'';
                        echo '<label style="display:inline-block;width:160px;"><input '.$checked.' type="checkbox" name="data['.$value.']" value="on"/> '.$value.' : </label><input type="text" name="var['.$value.']" value="'.$var[$value].'" /><br/>';
                    }
                    ?>                    
                </td>
            </tr>
            <tr>
                <th scope="row">Security Key:</th>
                <td>
                    <?php $fields= $this->get('fields');?>
                    <input type="text" name="fields[0][0]" value="<?php echo @$fields[0][0] ?>" class="regular-text code" /> 
                    <input type="text" name="fields[0][1]" value="<?php echo @$fields[0][1] ?>" class="regular-text code" />
                </td>
            </tr>
        </table>
        <?php echo submit_button();?>
    </form>
<?php //var_dump($this->options); 
?>    
</div>

