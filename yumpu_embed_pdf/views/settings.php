<div class="wrap">    
    <div id="icon-options-general" class="icon32"><br>
    </div>
    <h2><?php echo $title ?></h2>
    <div id="msgBox"><?php echo @$msg;?></div>
    <script>
        jQuery(document).ready(function($) {
            function checkAPI(){
                data = $('#APITokenForm').serialize();
                $this=$('#APICheck')
                $this.next('.ajaxLoader').css('display','inline-block');
                $.post(yumpu_embed_pdf.ajaxurl+'&controller=yumpu_embed_pdf_options&method=ajaxHandler',data,function(data){
                    $this.next('.ajaxLoader').css('display','none');                    
                    if(data=='OK'){
                        $('input[name=accessToken]').removeClass('api-ic').addClass('api-ok');
                        $('#msgBox').html('<div id="message" class="updated"><p>Aceess Token updated successfully. </p></div>').hide().slideToggle();
                        $('.msgbh').remove();
                        $('#APImsg').html(' Now write a <a href="<?php echo admin_url();?>post-new.php">new Post</a>');                        
                            setTimeout(function(){
                                $('#msgBox').find('div').slideToggle(function(){
                                    $('#msgBox').find('div').remove();
                                })
                            },10000)
                    }
                    else{
                        $('input[name=accessToken]').removeClass('api-ok').addClass('api-ic');
                        $('#APImsg').html('');
                    }
                })
            }
            //checkAPI();
            $('#createNewUser').click(function(e){
                $(this).find('span').html()=='+'?$(this).find('span').html('‒'):$(this).find('span').html('+');
                $('#iconNewUser').toggleClass('iconon')
                $('#showUserForm').slideToggle('fast');
                e.preventDefault();
            });

            $('#APICheck').click(function(e){
                checkAPI();
                e.preventDefault();
            })
            $('#createUserBtn').click(function(e){

                var error=0;
                if($('input#username').val()=='')
                    {
                        $('input#username').addClass('required');
                        error++;
                    }
                else
                    $('input#username').removeClass('required');

                if($('input#email').val()==''){
                    $('input#email').addClass('required');
                    error++;
                }                    
                else
                    $('input#email').removeClass('required');

                if($('input#fname').val()==''){
                    $('input#fname').addClass('required');
                    error++;
                }                    
                else
                    $('input#fname').removeClass('required');

                if($('input#lname').val()==''){
                    $('input#lname').addClass('required');
                    error++;
                }                    
                else
                    $('input#lname').removeClass('required');

                if(error==0){
                    data = jQuery('#userForm').serialize();
                    $this=$(this);
                    $this.next('.ajaxLoader').css('display','inline-block');
                    $.post(yumpu_embed_pdf.ajaxurl+'&controller=yumpu_embed_pdf_options&method=ajaxHandler',data,function(data){
                        $this.next('.ajaxLoader').css('display','none');
                        response = $.parseJSON(data);
                        if(response.status=='failed'){
                            alert(response.message);
                        }
                        else if(response.status=='ok'){
                            $('input[name=accessToken]').val(response.token);
                            //$('input[name=accessToken]').removeClass('api-ic').addClass('api-ok');
                            checkAPI();
                            $('#msgBox').html('<div id="message" class="updated"><p>Account Successfully created. <br/>IMPORTANT: Please check your E-Mail "'+$('input#email').val()+'" and activate your User Account"</p></div>').hide().slideToggle();
                            /*setTimeout(function(){
                                $('#msgBox').find('div').slideToggle(function(){
                                    $('#msgBox').find('div').remove();
                                })
                            },20000)*/

                        }
                    });
                }
                e.preventDefault();
            })
        });
    </script>
    <style>
        #iconNewUser{
            float: left;
            width: 28px;
            height: 28px;
            background-position: -300px -29px;
            background-image: url(./images/menu.png?ver=20121105);
        }
        div.iconon{
            background-position: -300px 3px !important;
        }
        span.ajaxLoader{
            background-image: url(./images/wpspin_light.gif);
            width: 16px;
            height: 16px;
            display: inline-block;
            background-repeat: no-repeat;
            margin-left: 5px;
            display: none;
        }
        input.api-ok{
            background: url(./images/yes.png) top right no-repeat;
        }
        input.api-ic{
            background: url(./images/no.png) top right no-repeat;   
        }
        em{
            color: red;
            line-height: 20px;
            font-size: 18px;
        }
        input.required{
            border-color: red;
        }
        #showUserForm em{
            color:#000;
        }
        #userForm td,th{
            padding-bottom: 10px;
        }
    </style>   
    <div class="clear"></div> 
    <div id="iconNewUser" class=""><br>
    </div>
    <h3 style="margin: 0;line-height: 35px;"><a style="text-decoration: none;" id="createNewUser" href="#"><span>+</span> Create Free Account</a></h3>
    <div id="showUserForm" style="margin-left: 30px; display: none">
        <form id="userForm">
            <input type="hidden" class="regular-text" name="APIMethod" value="createUser"/>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th>User Name<em>*</em></th>
                        <td><input type="text" class="regular-text" id="username" name="username" value="" /></td>
                    </tr>
                    <tr>
                        <th>Email<em>*</em></th>
                        <td><input type="text" class="regular-text" id="email" name="email" value="" /></td>
                    </tr>
                    <tr>
                        <th>First Name<em>*</em></th>
                        <td><input type="text" class="regular-text" id="fname" name="fname" value="" /></td>
                    </tr>
                    <tr>
                        <th>Last Name<em>*</em></th>
                        <td><input type="text" class="regular-text" id="lname" name="lname" value="" /></td>
                    </tr>
                    <tr>
                        <th>Gender<em>*</em></th>
                        <td>
                            <select name="gender">                        
                                <option value="M">Male</option>                            
                                <option value="F">Female</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2"><?php submit_button('Create','primary', 'submit',false,array('id'=>"createUserBtn"));?><span class="ajaxLoader"></span></th>
                    </tr>
                </tbody>
            </table>            
        </form>
        <br/>
    </div>
    <form style="margin-left: 30px;" id="APITokenForm">
        <table class="form-table">
            <tbody>
                <tr>
                    <th>API Token:</th>
                    <td>
                        <input type="text" class="regular-text" name="accessToken" value="<?php echo $this->get('accessTokenActive'); ?>"/>
                        <input type="hidden" class="regular-text" name="APIMethod" value="check"/>
                        <?php submit_button('Check','primary', 'submit',false,array('id'=>"APICheck") );?>
                        <span class="ajaxLoader"></span>                        
                    </td>
                </tr>
                <tr>
                    <th colspan="2">
                        <span id="APImsg"></span>
                    </th>
                </tr>
            </tbody>
        </table>                 
    </form>
</div>