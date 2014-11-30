<html>
    <head>
        <title>Yumpu Embed PDF</title>	
        <script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
        <script type="text/javascript" src="<?php echo $this->pluginurl ?>js/uploadify/jquery.min.js"></script>	
        <script type="text/javascript" src="<?php echo $this->pluginurl ?>js/uploadify/jquery.uploadify.js"></script>	
        <link rel="stylesheet" type="text/css" href="<?php echo $this->pluginurl ?>js/uploadify/uploadify.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->pluginurl ?>js/uploadify/style.css">        
        <script type="text/javascript">

        </script>	
    </head>
    <body>  
        <div style="margin:15px 0 0 7px" >
        <div id="icon-edit" class="icon32 icon32-posts-post"><br></div>
            <h2>Upload your PDF Document</h2>
            <div class="clear"></div>                 
        <form id="uploadForm">
            
        	<div id="titlewrap">
                <label class="hoverLabel" id="title-prompt-text" for="title">Enter your Document Title here</label>
                <input class="hoverInput" type="text" name="title" size="30" value="" id="title" autocomplete="off">
                <span style="display: inline-block;float:right;color:#bbb;" class="description">*Please enter more than 5 characters</span>
            </div>	        	
	        
        	<div class="clear"></div>	
            <div id="fileHolder" style="margin-right: 8px;">
                    <label  class="hoverLabel" id="fileDesc" >Add your File</label>
                    <input readonly="readonly" type="text" class="hoverInput" value=""/>                    
            </div>
            <input id="file_upload" name="file_upload" type="file" multiple="false">            
            <div id="queue">                                                        
            </div>            

            <div class="clear"></div>            
            <input style="float:left;" type="button" onclick="$('#file_upload').uploadify('upload')" value="Upload Document" class="button button-primary"/>            
            <div class="clear"></div>            
            
            <p></p>
        </form>
        <div id="documentDetails" style="display:none">
            <h4>PDF successfully uploaded</h4>
            <div id="preview">

            </div>
            <div class="clear"></div>
            <p></p>
            <input type="hidden" name="id" value="" id="documentId"/>
            <label>Width </label> <input size="8" type="text" name="width" id="width" value="512"/>
            <label>Height </label> <input size="8" type="text" name="height" id="height" value="384"/>
            <p></p>
            <div id="documentURL" style="display:none;">
            	Direct Link: <input type="text" readonly size="50" value="" />
            </div>
            <p></p>
            <input type="button" id="insertCode" class="button button-primary" value="Insert this PDF to the Document"/>
            <p></p>
        </div>
        <div id="uploadFailed" style="display:none;">
            <h4> Failed to create new Magazine : <span id="apiMsg"></span></h4>		
        </div>
        </div>

        <script type="text/javascript">
<?php $timestamp = time(); ?>				
    jQuery(document).ready(function($){			
        function refreshPreview(){
            var id=$('#documentId').val();
            $('#preview #reload').val('Getting Document...');
            $('#preview #reload').attr('disabled','disabled');

            $.get(window.parent.yumpu_embed_pdf.ajaxurl+'&controller=yumpu_embed_pdf_options&method=ajaxGetEmbedCode&id='+id,{},
            function(data){
                resp=$.parseJSON(data);
                if(resp.status=='ok'){
                    $('#preview').html(resp.code);
                    $('#documentURL input').val(resp.url);
                    $('#documentURL').show();
                }
                else{
                    $('#preview #reload').removeAttr('disabled');
                    $('#preview #reload').val('Refresh');
                    alert('Document Status - Your Document ist in Progress');
                }
            })
        }
        function initUploadify() {
            $('#file_upload').uploadify({
                'auto':false,
                'queueID':'queue',
                'buttonText' : 'Browse File',
                'formData'     : {
                    'timestamp' : '<?php echo $timestamp; ?>',
                    'token'     : '<?php echo md5('mhk63K' . $timestamp); ?>'
                },
                'swf'      : '<?php echo $this->pluginurl ?>js/uploadify/uploadify.swf',
                'uploader' : window.parent.yumpu_embed_pdf.ajaxurl+'&controller=yumpu_embed_pdf_options&method=pdfUploader',
                'onUploadSuccess' : function(file,data,response) {															
                    resp=$.parseJSON(data);					
                    $('#uploadForm').fadeOut(function(){						
                        if(resp.status=='200')
                        {
                            $('#documentDetails').fadeIn();	
                            $('#documentId').val(resp.id);
                            $('#preview').html('<p style="text-align: center;">Document in progress</p><p style="text-align: center;"><input type="button" class="button" id="reload" value="Refresh"/></p><p></p>');	
                            //refreshPreview();
                        }
                        else if(resp.status=='400')
                        {
                            $('#uploadFailed').fadeIn();
                            $('#uploadFailed #apiMsg').html(resp.message);
                        }
                    });											         		 
                },
                'width':85,                
                'height':22,
                'uploadLimit' : 1,
                'multi'    : false,
                'fileTypeDesc' : 'PDF Document',
                'fileTypeExts' : '*.pdf',
                'onSelectError' : function(file,errorCode,errorMsg) {        			
                    var msg;
                    if(errorCode==-100)
                        msg = 'You\'ve already selected another file. Please cancel that to select new file';
                    else
                        msg ="Error in selecting files";

                    this.queueData.errorMsg=msg;

                },
                'onSelect': function(file){
                	$('.uploadify-progress').hide();
                    $('#fileHolder').hide();
                    $('#file_upload-button').hide();
                    $('#file_upload').css('width','0');
                    var title = $('#title').val();
                    if(title!='' && title.length>5){
                        $('#file_upload').uploadify('upload');
                    }                   
                },
                'onCancel':function(){
                    $('#fileHolder').show();
                    $('#file_upload-button').show();
                    $('#file_upload').css('width','85px');
                },
                'onUploadStart' : function(file) {
                    var title = $('input#title').val();
                    if(title=='' || title.length<5){
                        alert('Please enter document title');
                        $('#file_upload').uploadify('stop');
                        return false;	
                    }        			
                    var formData = $('#file_upload').uploadify('settings','formData');
                    formData['title']=$('input#title').val();        		    
                    $('#file_upload').uploadify('settings','formData',formData);
                    $('.uploadify-progress').show();

                }                
            });
        }
        initUploadify();

        $('#addNewPDF').click(function(e){				
            $('#uploadForm').show();
            $('#uploadForm input#title').val('');


            $('#file_upload').uploadify('destroy');
            initUploadify();
            $('#queue').html('');
            // $('#file_upload').uploadify('cancel', '*',true) //doesn't work on IE8
            $('#documentDetails').hide();
            $('#uploadFailed').hide();				
            $('#documentURL').hide();	
            e.preventDefault();
        })
        $('#preview').on('click','#reload',function(e){				
            refreshPreview();
            e.preventDefault();
        })
        $('#insertCode').click(function(e){
            var width = $('input#width').val();
            var height = $('input#height').val();
            var id=$('#documentId').val();
            var code='[Yumpu-Embed documentid="'+id+'" width="'+width+'" height="'+height+'"]';
            tinyMCEPopup.editor.execCommand('mceInsertContent', false,code );
            tinyMCEPopup.close();
        })
        $("#documentURL input[type=text]").click(function() {
   			$(this).select();
		});
		$( document ).ajaxError(function(event, jqxhr, settings, exception) {
  			console.log(jqxhr);
  			console.log(settings);
  			console.log(exception);
  			console.log(event);
  			alert('Server Error. Please Try Again.');		
  		})
        $('input.hoverInput').focus(function(e){
            var attr = $(this).attr('readonly');

            if (attr!='readonly') {
                $(this).prev('label.hoverLabel').hide();
            }           
                
        });
        $('input.hoverInput').blur(function(e){
            if($(this).val()=='')
                $(this).prev('label.hoverLabel').show();
        });
    })
        </script>
    </body>
</html>