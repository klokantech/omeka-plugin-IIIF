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
	<li><a href="#iiif-server" class="active">Custom IIIF server</a></li>
	<li><a href="#iiif-hosting">IIIFHosting.com</a></li>
</ul>

<section class="seven columns alpha" id="edit-form">
	<div id="iiif-server" style="display: block;">
		<h2>Custom IIIF server</h2>
		<p class="element-set-description" id="iiif-server-description">This plugin adds support for IIIF (http://iiif.io/) APIs. Items in Omeka can load images from any existing IIIF server. Each Omeka item with IIIF image automatically exposes the presentation metadata via the IIIF Manifest URL - so it can be easily displayed and used in other tools such as <a href="http://projectmirador.org/">Mirador</a>, <a href="http://www.georeferencer.com/">Georeferencer</a> and others.
    </p>
    <p class="element-set-description" id="iiif-server-description">
    This Omeka instance can easily display images from an extermal image server compatible with IIIF Image API protocol. In case you want to install one check
    <a href="http://www.iiifserver.com">http://www.iiifserver.com</a> or others linked from <a href="http://iiif.io/">http://iiif.io/</a></p>
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
		<h2>IIIFHosting.com</h2>
		<p class="element-set-description" id="iiif-hosting-description">If <a href="http://www.iiifhosting.com">IIIFHosting.com</a> user account is activated here, the new images added to items in this Omeka administration interface will be uploaded also to IIIFHosting.com account - and any JPEG or TIFF will get fast zoomable viewer and responsive image service endpoint allowing interoperatibility with 3rd party IIIF compatible tools.</p>
		<div class="field">
    	<?php echo $view->formLabel('ftp_url', 'Client ID'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('ftp_url', $ftp_url, array('class' => 'textinput')); ?>
        		<p class="explanation">
            		The client identifier - visible as subdomain of IIIFHosting.com
        		</p>
    		</div>
		</div>

		<div class="field">
    	<?php echo $view->formLabel('ftp_user', 'Username'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('ftp_user', $ftp_user, array('class' => 'textinput')); ?>
        		<p class="explanation">
            		The username for IIIFHosting.com storage.
        		</p>
    		</div>
		</div>

		<div class="field">
    	<?php echo $view->formLabel('ftp_pass', 'Password'); ?>
    		<div class="inputs">
        	<?php echo $view->formText('ftp_pass', $ftp_pass, array('class' => 'textinput')); ?>
        		<p class="explanation">
            	The password for IIIFHosting.com storage.
        		</p>
    		</div>
		</div>
	</div>
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
  
</section>
