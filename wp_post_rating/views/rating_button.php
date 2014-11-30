<style>
/*
	.rateIt{
		float: left;
		width: 15%;
	}
	.rateIt a {
		display: inline-block;
		text-decoration: none;
		zoom: 1;
		color: #222;
		font-weight: bold;
		padding: 0px 8px;
		line-height: 2;
		border: 1px solid #907164;
		-moz-border-radius: 5px;
		border-radius: 5px;
		-moz-box-shadow: #ddd 0px 3px 3px;
		box-shadow: #ddd 0px 3px 3px;
		font-family: arial,verdana,helvetica,sans-serif;
		background-color: #eae7db;
		background: linear-gradient(top, #f3f3e7,#e7e2c5);
		background: -ms-linear-gradient(top, #f3f3e7, #e7e2c5);
		background: -moz-linear-gradient(top, #f3f3e7, #e7e2c5);
		background: -o-linear-gradient(top, #f3f3e7, #e7e2c5);
		background: -webkit-linear-gradient(top, #f3f3e7, #e7e2c5);
		outline: 0;
		white-space: nowrap;
	}
	.rateIt a:hover {
		color: black;
		text-decoration: none;
		cursor: pointer;
		background-color: #f0ede5;
		background: linear-gradient(top, #f8f8f1,#ebe7d0);
		background: -ms-linear-gradient(top, #f8f8f1, #ebe7d0);
		background: -moz-linear-gradient(top, #f8f8f1, #ebe7d0);
		background: -o-linear-gradient(top, #f8f8f1, #ebe7d0);
		background: -webkit-linear-gradient(top, #f8f8f1, #ebe7d0);
	}
	.rateIt a:active {
		background-color: #eae7db;
		background: linear-gradient(top, #f3f3e7,#e7e2c5);
		background: -ms-linear-gradient(top, #f3f3e7, #e7e2c5);
		background: -moz-linear-gradient(top, #f3f3e7, #e7e2c5);
		background: -o-linear-gradient(top, #f3f3e7, #e7e2c5);
		background: -webkit-linear-gradient(top, #f3f3e7, #e7e2c5);
		-moz-box-shadow: inset 0px 1px 3px rgba(0,0,0,0.2);
		box-shadow: inset 0px 1px 3px rgba(0,0,0,0.2);
	}
	.rateIt .rateNotice{
		color: green;
		font-weight: bold;
	}
	*/
</style>
<div class="rateWrap">

<div class="ratingCount">
	<?php if($count):?>
     	<a href="<?php the_permalink();?>" class="voteCounter" title=""><?php echo number_format($count,0) ?> people liked it</a>
 	<?php else:?>
 		<a href="<?php the_permalink();?>" class="voteCounter" title="">No Like Yet!</a>
 	<?php endif;?>
</div>
<div class="rateIt">	
	<?php if ( !is_user_logged_in() && $this->get('votingMethod')=='members' ) :?>
	<a href="<?php echo wp_login_url( getRequestUri() ); ?>" rel="nofollow">like</a>		
	<?php elseif($isLiked):?>	
	<span class="rateNotice" <?php echo ($isLiked)?'style="display:inline;"':'style="display:none"' ?>>Liked!</span>
	<?php else:?>
	<a post-id="<?php echo $post->ID; ?>" href="#" class="rateBtn" rel="nofollow">like</a>
	<span class="rateNotice" style="display:none">Liked!</span>
	<?php endif;?>
</div>
</div>