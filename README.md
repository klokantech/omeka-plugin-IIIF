# Omeka IIIF Plugin

This plugin adds support for IIIF (http://iiif.io/) APIs into [Omeka](http://www.omeka.org/). Items created in Omeka can load images from any existing IIIF server. Each Omeka item with IIIF image exposes the presentation metadata via the IIIF Manifest URL - so the items can be easily displayed and used in other tools such as Mirador, UniversalViewer or Georeferencer. 
Users can configure in the plugin the IIIFHosting.com service - then the new images such as JPEG or TIFFs uploaded via administration of Omeka website will be turned into IIIF image service automatically and are going to be available in a fullscreen zoomable viewer and in variable pixel size via a responsive image service.

## Installation

The content of this repository needs to be copied to `/plugins/Iiif/` directory in your Omeka installation.

After the installation of this plugin the Omeka storage adapter must be changed in the main configuration (which is located at `/application/config/config.ini`). The following line needs to be added there:
```
storage.adapter = "Iiif_Storage_Adapter_Iiif"
```
**In case you want to deactivate or uninstall the plugin - you must first remove this line from the config.ini of your Omeka!**

## Usage

After installation every item in Omeka will get in the Editing mode a IIIF sidebar, similar to:

<img width="767" alt="screen shot 2015-12-08 at 09 51 01" src="https://cloud.githubusercontent.com/assets/59284/11656658/f47457fe-9db6-11e5-8137-809d6d102fe1.png">

There it is possible to connect the metadata record of the item in Omeka with a IIIF image. If user provides a link to IIIF info.json, the connected image will appear under "Files" section - and Omeka will automatically use the image service for thumbnails, in exhibitions and also in a fast zoomable viewer in the public website. The Manifest compatible with the IIIF Presentation API for each item (with one image or a sequence of images) going to be available under link ending with /item/show/xxxxx/manifest.json.

The section "Plugins" in the administration (/admin) of your Omeka - the plugin has "Configuration" page.

On this page the user can provide address to his own image server (such as "http://mydomain.com/iiif/"). In such case the items can be linked to images just by providing correct short "identifer" in the sidebar described above.

For complete integration of IIIF with the Omeka - allowing for example direct upload of images (JPEG or TIFF) into a IIIF service, users can purchase a plan on IIIFHosting.com service. In case correct institution name, user and password is provided Omeka will submit copies of the images to the dedicated hosting storage where these are going to be converted to JPEG2000 and exposed via IIIF.

If the plugin is configured correctly and enabled there will be zoomable images on the `item/show` public pages for particular items.

Plugin needs to be triggered from `[your_theme]/items/show.php`. The most useful way to do it follows:
```
<?php echo get_specific_plugin_hook_output('Iiif', 'public_items_show', array('view' => $this, 'item' => $item)); ?>
```
