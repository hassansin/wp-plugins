 
// Docu : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins

(function() {
    // Load plugin specific language pack	
	 
    tinymce.create('tinymce.plugins.yumpu_embed_pdf', {
		
        init : function(ed, url) {                    
            url2=url.slice(0,-2);            
            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');	
            ed.addCommand('yumpu_embed_pdf_cmd', function() {                
                ed.windowManager.open({
                    title : 'Yumpu Embed PDF',
                    file : url2+'lib/editorWindow.php',
                    width : 600,
                    height : 320,
                    inline : 1,
                    resizable:false
                });
            });

            // Register example button
            ed.addButton('yumpu_embed_pdf', {
                title : 'Yumpu Embed PDF',				
                cmd : 'yumpu_embed_pdf_cmd',
                image : url + '/icon.png',                
            });

            // Add a node change handler, selects the button in the UI when a image is selected
            ed.onNodeChange.add(function(ed, cm, n) {
                cm.setActive('yumpu_embed_pdf', n.nodeName == 'IMG');
            });
        },
        createControl : function(n, cm) {
            return null;
        },
        getInfo : function() {
            return {
                longname : "Yumpu Embed PDF",
                author : 'Forhadur Reza',
                authorurl : 'mailto:rezatxe@gmail.com',
                infourl : 'mailto:rezatxe@gmail.com',
                version : "1.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('yumpu_embed_pdf', tinymce.plugins.yumpu_embed_pdf);
})();



