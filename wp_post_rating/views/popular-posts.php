<div id="popular-posts">
	<?php 
		
		if($query->have_posts()):
			while($query->have_posts()): $query->the_post();
		?>
	<article class="post">
		<div>
			<header class="entry-header">
				<h2>
				<a href="<?php the_permalink();?>" title="<?php the_title();?>"><?php the_title();?></a>
			</h2>
			</header>
			<?php do_action('print-like-btn')?>		
			<div class="entry-content">
				<?php 
				if($this->get('content')=='excerpt')
					the_excerpt();
				elseif($this->get('content')=='full')
					the_content();
				elseif($this->get('content')=='none')
					;				
				?>
			</div>
		</div>

	</article>
	<div class="clear"></div>
<?php endwhile; 
endif;
?>
</div>
<?php
$total_pages = $query->max_num_pages;  
  
if ($total_pages > 1){  
  $current_page = max(1, get_query_var('paged'));  
    
  echo paginate_links(array(  
      'base' => get_pagenum_link(1) . '%_%',  
      'format' => '/page/%#%',  
      'current' => $current_page,  
      'total' => $total_pages,  
    ));  
} 
?>