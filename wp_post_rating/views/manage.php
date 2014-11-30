<div class="wrap">
    <div id="icon-options-general" class="icon32"><br>
    </div>
    <h2><?php echo $title ?></h2>
    <script>

    </script>
    <style>
    </style>
    <form action="" method="post">
        <input type="hidden" name="<?php echo $this->slug . '_controller' ?>" value="wppostrating_options"/>
        <input type="hidden" name="<?php echo $this->slug . '_method' ?>" value="update"/>
        <table class="form-table">
            <tbody>
                <!-- 
                <tr>
                    <th>Post Types </th>
                    <td>
                        <?php 
                              $args = array(
                                    'public'   => true,
                                    'show_ui' => true
                                );
                            $post_types=get_post_types($args,'names'); 
                            foreach ($post_types as $post_type ) {
                                $checked = in_array($post_type, $this->get('postType'))?'checked':'';
                                echo '<label><input '.$checked.' type="checkbox" name="postType[]" value="'.$post_type.'"/> '. ucfirst($post_type). '</label><br/>';
                            }
                        ?>
                    </td>
                </tr>                  -->
                 <tr>
                    <th>Who Can vote</th>
                    <td>
                        <label><input <?php echo $this->get('votingMethod')=='members'?'checked':'' ?> type="radio" name="votingMethod" value="members"/>Members only</label><br/>
                        <label><input <?php echo $this->get('votingMethod')=='any'?'checked':'' ?> type="radio" name="votingMethod" value="any"/>Anyone</label>
                    </td>
                 </tr>
                <tr>
                    <th>Include Categories</th>
                    <td>
                        <?php 
                              $args = array(
                                    'hide_empty'   => false,                                    
                                );
                            $categories=get_categories($args);                             
                            foreach ($categories as $category ) {
                                $checked = in_array($category->cat_ID, $this->get('category__in'))?'checked':'';
                                $empty = $category->count=='0'?'(empty)':'';
                                echo '<label><input '.$checked.' type="checkbox" name="category__in[]" value="'.$category->cat_ID.'"/> '. ucfirst($category->name). '</label> <em>'.$empty.'</em><br/>';
                            }
                        ?>
                    </td>
                </tr>                                 
                 <tr>
                    <th>Post Contents</th>
                    <td>
                        <label><input <?php echo $this->get('content')=='excerpt'?'checked':'' ?> type="radio" name="content" value="excerpt"/>Excerpt </label><br/>
                        <label><input <?php echo $this->get('content')=='full'?'checked':'' ?> type="radio" name="content" value="full"/>Full Content</label><br/>
                        <label><input <?php echo $this->get('content')=='none'?'checked':'' ?> type="radio" name="content" value="none"/>Only Title</label>
                    </td>
                 </tr>
                 <tr>
                    <th>Exclude Posts</th>
                    <td>
                        <input value="<?php echo implode(',',$this->get('exclude'))?>" type="text" name="exclude" class="regular-text" /> <span class="description">Comma-separated post IDs</span>
                    </td>
                 </tr>
                <tr>
                    <th> CSS to style button and post
                    </th>
                    <td>                    
                        <textarea style='font-family: "Lucida Console", "Lucida Sans Typewriter", Monaco, "Bitstream Vera Sans Mono", monospace;' name="css" rows="15" cols="100"><?php echo $this->get('css') ?></textarea>
                    </td>
                </th>
            </tbody>
        </table>
        <?php submit_button('Save Options') ?>
    </form>
    <div>
        <h3>Instruction:</h3>
        <ul style="list-style-type: disc;margin-left: 20px;">
            <li>Use shortcode <code> [posts-by-vote ipp="10" ]</code> in any page/post to generate popular posts (ipp= items per page)</li>
            <li>To add a 'Like' button in single posts, insert this code <code>&lt;?php do_action('print-like-btn')?&gt;</code> into your theme file
                <br/>or You can add the shortcode <code>[print-like-btn]</code> inside any post 
            </li>
            <li>
                Change CSS to style the like button and page template. <br/>
                To further modify the like button and page template edit <b>popular-posts.php</b> and <b>rating_button.php</b> in <code>wp-content/plugins/wp_post_rating/views</code> folder
            </li>
        </ul>
    </div>
</div>

