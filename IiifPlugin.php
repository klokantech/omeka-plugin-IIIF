<?php

define('IIIF_HOSTING_PLUGIN_DIR', PLUGIN_DIR . '/IIIF');

class IiifPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
    	'install',
		'uninstall',
		'define_routes',
		'config_form',
		'config',
		'public_head',
		'public_items_show',
		'before_save_file',
		'after_save_file',
		'after_delete_file',
		'admin_items_show_sidebar'
	);
	
	/**
     * Install the plugin.
     */
    public function hookInstall()
    {
        $db = $this->_db;
        $sql = "ALTER TABLE $db->File ADD INDEX `filename` (`filename` ( 32 ))";
        $db->query($sql);
        
        set_option('iiif_ftp_url', '');
        set_option('iiif_ftp_user', '');
        set_option('iiif_ftp_pass', '');
        set_option('iiif_server', '');
        set_option('file_mime_type_whitelist', 'image/jpeg,image/tiff');
    }
    
    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $db = $this->_db;
        $sql = "ALTER TABLE $db->File DROP INDEX `filename`";
        $db->query($sql);
        
        delete_option('iiif_ftp_url');
        delete_option('iiif_ftp_user');
        delete_option('iiif_ftp_pass');
        delete_option('iiif_server');
    }

    public function hookdefineRoutes($array)
    {
        $router = $array['router'];
        $route = new Zend_Controller_Router_Route('items/show/:id/manifest.json',
            		array('controller' => 'manifest',
                	   'module' => 'iiif',
                       'action' => 'manifest'));
                       
		$router->addRoute('iiif_manifest', $route);
		
		$route = new Zend_Controller_Router_Route('items/show/:id/iiif_mapping',
            		array('controller' => 'mapping',
                	   'module' => 'iiif',
                       'action' => 'mapping'));
                       
		$router->addRoute('iiif_mapping', $route);
		
		$route = new Zend_Controller_Router_Route('items/show/:id/iiif_add',
            		array('controller' => 'mapping',
                	   'module' => 'iiif',
                       'action' => 'add'));
                       
		$router->addRoute('iiif_add', $route);
    }

    public function hookConfigForm() 
    {
        include 'config_form.php';
    }

    public function hookConfig($args)
    {
        $post = $args['post'];
        set_option('iiif_ftp_url', $post['ftp_url']);
        set_option('iiif_ftp_user', $post['ftp_user']);    
        set_option('iiif_ftp_pass', $post['ftp_pass']);
        set_option('iiif_server', $post['iiif_server']);
    }
    
    public function hookPublicHead($args)
    {
        if (substr(current_url(), 0, 11) == '/items/show') {
            queue_js_file('openseadragon.min');
	        queue_js_file('zoom');
	        queue_css_file('zoom');
	    }
    }
    
    public function hookPublicItemsShow($args)
    {
        $item = $args['item'];

        if (count($item->Files) == 0 or !item_image('fullsize')) return;
        
        echo "<div id='detail-image-container'>\n";
        echo "	<div id='detail-image'>\n";
        echo item_image('fullsize');
        echo "	</div>\n";
        echo "	<div id='zoom-image' ></div>\n";
		echo "	<div id='zoom-image-close'></div>\n";
        
        $metadata = array();
    	
    	foreach ($item->Files as $file) {
		    $tmp = json_decode($file->metadata, True);
		    
		    if (array_key_exists('iiif', $tmp) and $tmp['iiif'] != array()) {
		    	array_push($metadata, json_encode($tmp['iiif'], JSON_UNESCAPED_SLASHES));
		    }
		}
                
        $script = "	<script>\n
		    var title='".metadata('item', array('Dublin Core', 'Title'))."';
    	    var images = [".implode(",", $metadata)."];
        	</script>\n";
        
    	echo $script;
    	
    	echo "	<script>zoom('detail-image','zoom-image', images);</script>\n";
    	echo "</div>\n";
    	echo "<div id='manifest-url'>\n";
    	echo "<span>IIIF Manifest URL: </span><a href='".absolute_url("items/show/$item->id/manifest.json")."'>".absolute_url("items/show/$item->id/manifest.json")."</a>\n";
    	echo "</div>\n";
    	
    }
    
    public function hookBeforeSaveFile($args)
    {
		# Hack to disable creation of thumbnails internally in Omeka
		Zend_Registry::getInstance()->offsetUnset('file_derivative_creator');
    }
    
    public function hookAfterSaveFile($args)
    {
    	$file = $args['record'];
    	$insert = $args['insert'];
    	
    	if ($insert){
    		$ftp_url = get_option('iiif_ftp_url') . '-ftp.iiifhosting.com';
			$ftp_user = get_option('iiif_ftp_user');	
			$ftp_pass = get_option('iiif_ftp_pass');
			$error = False;
			
			$conn = ftp_connect($ftp_url);
			
			if (!$conn or !ftp_login($conn, $ftp_user, $ftp_pass)) {
				Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Can't connect to storage server.", 'error');
				$error = True;
			}
			
			if (!$error) {
				if (ftp_put($conn, $file->filename, '/tmp/'.$file->filename, FTP_BINARY) == False) {
					Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Error during upload the file to storage server.", 'error');
					$error = True;
				}
			}
			
			try {
				unlink('/tmp/'.$file->filename);
			} catch (Exception $e) {
			}
			
			if ($conn) {
				ftp_close($conn);
			}
			
			if ($error) {
				Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(WEB_ROOT . '/admin/items/edit/' . $file->item_id);
			}
			
    		$file->size = 0;
    		$file->stored = 1;
    		$file->has_derivative_image = 1;
    		$file->metadata = '{"iiif":{}}';
    		$file->save();
    	}
    }
    
    public function hookAfterDeleteFile($args)
    {
    	$file = $args['record'];
    	
    	if ($file->stored == 1) {
	    	$ftp_url = get_option('iiif_ftp_url') . '-ftp.iiifhosting.com';
			$ftp_user = get_option('iiif_ftp_user');	
			$ftp_pass = get_option('iiif_ftp_pass');
			$error = False;
			
			$conn = ftp_connect($ftp_url);
			
			if (!$conn or !ftp_login($conn, $ftp_user, $ftp_pass)) {
				$error = True;
			}
			
			if (!$error) {
				ftp_delete($conn, $file->filename);
			}
			
			if ($conn) {
				ftp_close($conn);
			}
		}
    }
    
    public function hookAdminItemsShowSidebar($args)
    {
    	$item = $args['item'];

    	echo "<div class='panel'><h4>IIIF image service</h4><div>";
    	echo "<p><a href='/items/show/$item->id/iiif_mapping'>Verify availability</a></p>";
    	echo '<div id="add-tags">
    			<form method="post" enctype="multipart/form-data" id="iiif_form" action="/items/show/'.$item->id.'/iiif_add">
        			<label>Identifier or link to info.json:</label>           
        			<p><input type="text" name="iiif_input" size="20" class="textinput" value="" /></p>
        			<input type="submit" name="iiif_submit" class="green button" value="Add image" />
        		</form>
    		</div>';
        echo "</div></div>";
    }
}
?>
