IIIF Hosting Plugin

This plugin provides zoomable images via Klokantech IIIF hosting service.

There are some options which need to be set after installation of this plugin:

- FTP URL and credentials - connection to FTP backend - place where the source images are saved
- IIIF Server URL - base URL of server which provides zoomable images (converted from source images)
- There is python script `discover.py` in the `python` directory in this plugin which needs to be run on the server with omeka installation. It is designed to run under supervisor, so basic configuration is provided in `supervisor.conf`. It have to be updated (especially path to omeka installation) and put into supervisor configuration usually into `/etc/supervisor/conf.d/`
- Configuration of the storage adapter inside of Omeka main configuration ([path_to_omeka]/application/config/config.ini) - `storage.adapter = 'IiifHosting_Storage_Adapter_IiifHosting'` - this have to be done only after the plugin is enabled
