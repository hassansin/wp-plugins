<div class="wrap">
    <div id="icon-options-general" class="icon32"><br>
    </div>
    <h2><?php echo $title ?></h2>
    <script>

    </script>
    <style>
    </style>
    <table class="form-table">
        <tbody>
            <?php if($this->customPostId =='' || (int)$this->customPostId ==0 ):?>
            <tr>
                <th>Select Page to Embed</th>
                <td>
                    <select id="postId" name="postId">
                        <option value="">Select Page</option>
                        <?php          

                        echo '<optgroup label="Pages:">';
                        $args=array('post_type' => 'page');
                        $pages = get_posts($args);
                        foreach ( $pages as $page ) {
                            $option = '<option value="' . md5($this->secret.'_'. $page->ID). '">';
                            $option .= $page->post_title;
                            $option .= '</option>';
                            echo $option;
                        }
                        echo '</optgroup>';
                        echo '<optgroup label="Posts:">';
                        $args=array('post_type' => 'post');
                        $pages = get_posts($args);
                        foreach ( $pages as $page ) {
                            $option = '<option value="' .  md5($this->secret.'_'. $page->ID) . '">';
                            $option .= $page->post_title;
                            $option .= '</option>';
                            echo $option;
                        }
                        echo '</optgroup>';
                        ?>
                    </select>
                </td>
            </tr>
            <?php else: ?>
            <input type="hidden" name="postId" id="postId" value=<?php echo md5($this->secret.'_'. $this->customPostId) ?> />
            <?php endif;?>
            <tr>
                <th>Remote iFrame Size</th>
                <td>
                    <label style="display: inline-block;width:60px">Width: </label><input type="text" name="iframeWidth" id="iframeWidth" value="500"/> PX<br/><br/>
                    <label style="display: inline-block;width:60px">Height:</label><input type="text" name="iframeHeight" id="iframeHeight" value="300"/> PX<br/><br/>
                    <label><input type="checkbox" name="iframeScroll" id="iframeScroll" value="1"/> Include Scroll Bars </label><br/><br/>
                    <!--<label><input type="checkbox" name="iframeResize" id="iframeResize" value="1"/> Auto-resize Height </label><br/><br/>-->
                    <label><input type="checkbox" name="fullpage" id="fullpage" value="1"/> Load full page </label><br/><br/>
                    <input type="button" class="button" id="generateCode" value="Generate Embed Code"/>

                </td>
            </tr>
        </tbody>
        
    </table>
    <div>
        <h3>Edit Template File</h3>
        <form action="" method="post">
            <input type="hidden" name="<?php echo $this->slug . '_controller' ?>" value="<?php echo $_GET['page'] ?>"/>
            <input type="hidden" name="<?php echo $this->slug . '_method' ?>" value="update"/>
            <textarea name="template" rows="10" cols="100"><?php echo file_get_contents($this->plugindir.'/views/template.php') ?></textarea>
            <br/>
            <input type="submit" class="button" name="update" value="Save Template File"/>&nbsp;<input type="submit" class="button" name="reset" id="resetCode" value="Reset Template File"/>
        </form>
    </div>

    <div>
        <h3>Embed HTML Code</h3>
        <textarea id="embedCode" rows="10" cols="100"></textarea>
        <br/>
        <input type="button" class="button" id="copyCode" value="Copy Embed Code to Clipboard"/>

        <script src="<?php echo $this->pluginurl.'js/'?>ZeroClipboard.js"></script>
        <script language="JavaScript">
          jQuery(document).ready(function($) { 

            $('#generateCode').on('click',function(){
                if($('#postId').val()=='')
                    {
                        alert('Select a page first!');
                        return;
                    }

                var scroll = $('#iframeScroll:checked').val()=='1'?'true':'false',
                //ah = $('#iframeResize:checked').val()=='1'?'true':'false',
                fp = $("#fullpage:checked").val()=='1'?'true':'false';

                $('#embedCode').html('<div id="wpee_container"></div>&lt;script src="<?php echo site_url(); ?>?wpee_scripts=true&id='+$('#postId').val()+'&w='+$('#iframeWidth').val()+'&h='+$('#iframeHeight').val()+'&scroll='+scroll+'&fp='+fp+'" &gt;&lt;/script&gt;');
                clip.setText($('#embedCode').val());
            })

            var clip = new ZeroClipboard($("#copyCode"), {
              moviePath: "<?php echo $this->pluginurl.'js/'?>ZeroClipboard.swf"
            });            

            clip.on('noflash', function (client) {
              $("#generateCode").hide();              
            });
            /*
            clip.on('complete', function (client, args) {
                alert('Embed code copied to clipboard!');
            });
            */

            $("#embedCode").on("change", function(){
              clip.setText($(this).val());
            });
          })
</script>
    </div>
</div>