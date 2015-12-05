#IIIF Plugin

This plugin provides zoomable images via IIIF Server and can cooperate with Klokantech IIIF Hosting service.

Source files from this repository needs to be installed to `[omeka_site_installation_path]/plugins/Iiif/`.
After the installation of this plugin the Omeka storage adapter needs to be configured in the Omeka main configuration (which is located at `[omeka_site_installation_path]/application/config/config.ini`).
The following configuration needs to be added there:

storage.adapter = "Iiif_Storage_Adapter_Iiif"

If this plugin needs to be removed the configuration MUST BE REMOVED before deactivation of the plugin.

***IIIF server URL*** is the main option which needs to be configured. It tells where thumbnails and zoomable images are hosted.
If the IIIF info.json file for particular image is located somewhere like `http://demo.iiifhosting.com/iiif/mona_lisa/info.json` the IIIF server option needs to be set as `http://demo.iiifhosting.com/iiif`.

There are another options for customers of Klokantech IIIF Hosting service where images can be hosted too. There are these options:

* ***Subdomain***
* ***Username***
* ***Password***

It they are correctly configured there is a possibility of uploading source images directly with Omeka administration.

IIIF image service panel is added to `items/show` Admin pages, where can be zoomable images added to particular item. They can be added directly with `ID` of image on the IIIF server, or there can be triggered previously uploaded images to IIIF hosting (images aren't usually available immediately because conversion and manipulation have to be done on the server side).

If the plugin is configured correctly and enabled there will be zoomable images on the `item/show` public pages for particular items.

Plugin needs to be triggered from `[your_theme]/items/show.php`. The most useful way to do it follows:
```
<?php echo get_specific_plugin_hook_output('Iiif', 'public_items_show', array('view' => $this, 'item' => $item)); ?>
```
