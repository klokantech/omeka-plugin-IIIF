<?php
$ftp_url                                 = get_option('iiif_ftp_url');
$ftp_user                                = get_option('iiif_ftp_user');	
$ftp_pass                                = get_option('iiif_ftp_pass');
$iiif_server                             = get_option('iiif_server');

$view = get_view();
?>

<script type="text/javascript" src="/admin/themes/default/javascripts/tabs.js" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
//<![CDATA[
// TinyMCE hates document.ready.
jQuery(window).load(function () {
    Omeka.Tabs.initialize();

});
//]]>
</script>
<ul id="section-nav" class="navigation tabs">
	<li><a href="#iiif-server" class="active">IIIF Server</a></li>
	<li><a href="#iiif-hosting">IIIF Hosting</a></li>
</ul>

<section class="seven columns alpha" id="edit-form">
	<div id="iiif_plugin_config_info" style="border: 3px solid red; margin-bottom: 10px; padding: 5px;">
	<?php
		$adapter = Zend_Registry::get('bootstrap')->storage->getAdapter();
		
		if ($adapter instanceof Iiif_Storage_Adapter_Iiif) {
			echo 'Before eventual deactivation of this plugin the Omeka storage adapter configuration <span style="color:red">MUST BE REMOVED</span> in the Omeka main configuration (located at [site_installation_path]/application/config/config.ini).<br/> Remove following line there:<br/><br/>storage.adapter = "Iiif_Storage_Adapter_Iiif"';
		} else {
			echo 'After activation of this plugin the Omeka storage adapter needs to be configured in the Omeka main configuration (located at [site_installation_path]/application/config/config.ini).<br/> Add following line there:<br/><br/>storage.adapter = "Iiif_Storage_Adapter_Iiif"';
		}
	?>
	</div>
	<div id="iiif-server" style="display: block;">
		<h2>IIIF Server</h2>
		<p class="element-set-description" id="iiif-server-description">The IIIF Server is software to provide zoomable images. For more information see, <a href="http://www.iiifserver.com">http://www.iiifserver.com</a></p>
		<div class="field">
    	<?php echo $view->formLabel('iiif_server', 'IIIF server URL'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('iiif_server', $iiif_server, array('class' => 'textinput')); ?>
        		<p class="explanation">
            		The URL of IIIF server with 'http://'.
        		</p>
    		</div>
		</div>
	</div>
	<div id="iiif-hosting" style="display: none;">
		<h2>IIIF Hosting</h2>
		<p class="element-set-description" id="iiif-hosting-description">The IIIF Hosting is hosting service for zoomable images. For more information see, <a href="http://www.iiifhosting.com">http://www.iiifhosting.com</a></p>
		<div class="field">
    	<?php echo $view->formLabel('ftp_url', 'Subdomain'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('ftp_url', $ftp_url, array('class' => 'textinput')); ?>
        		<p class="explanation">
            		The subdomain part of URL of IIIF Hosting.
        		</p>
    		</div>
		</div>

		<div class="field">
    	<?php echo $view->formLabel('ftp_user', 'Username'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('ftp_user', $ftp_user, array('class' => 'textinput')); ?>
        		<p class="explanation">
            		The username for IIIF Hosting storage.
        		</p>
    		</div>
		</div>

		<div class="field">
    	<?php echo $view->formLabel('ftp_pass', 'Password'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('ftp_pass', $ftp_pass, array('class' => 'textinput')); ?>
        		<p class="explanation">
            	The password for IIIF Hosting storage.
        		</p>
    		</div>
		</div>
	</div>
</section>
