(function($){
	$('.rateIt a.rateBtn').click(function(e){
		$this=$(this);
		$id = parseInt($this.attr('post-id'));
		if($id<=0){
			e.preventDefault();	
			return;
		}
		$url = wp_post_rating.ajaxurl+'&controller=wppostrating_options&method=ratePost'+'&id='+$id;		
		$action = wp_post_rating.action;
		$.post($url,{},
			function(data){
				$res=$.parseJSON(data);
				if($res.stat=='ok'){
					$this.fadeOut(200,function(){
						$this.next('.rateNotice').show();
						$this.parents('.rateWrap').find('.voteCounter').html($res.count+' people liked it');
						$this.remove();						
					})
				}

		})
		e.preventDefault();
		
	})

})(jQuery);