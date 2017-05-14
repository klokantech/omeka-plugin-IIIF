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
        'after_save_item',
        'after_save_file',
        'admin_items_panel_buttons'
    );

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        $db = $this->_db;
        $sql = "ALTER TABLE $db->File ADD INDEX `filename` (`filename` ( 32 ))";
        $db->query($sql);

        set_option('iiifhosting_customer', '');
        set_option('iiifhosting_secure_payload', '');
        set_option('iiifhosting_ingest_api', 'https://admin.iiifhosting.com/ingest/');
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

        delete_option('iiifhosting_customer');
        delete_option('iiifhosting_secure_payload');
        delete_option('iiifhosting_ingest_api');
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

        $route = new Zend_Controller_Router_Route('items/edit/:id/iiif_mapping',
                    array('controller' => 'mapping',
                       'module' => 'iiif',
                       'action' => 'mapping'));

        $router->addRoute('iiif_mapping', $route);

        $route = new Zend_Controller_Router_Route('items/edit/:id/iiif_add',
                    array('controller' => 'mapping',
                       'module' => 'iiif',
                       'action' => 'add'));

        $router->addRoute('iiif_add', $route);

        $route = new Zend_Controller_Router_Route('iiif_ingest',
                    array('controller' => 'ingest',
                       'module' => 'iiif',
                       'action' => 'ingest'));

        $router->addRoute('iiif_ingest', $route);
    }

    public function hookConfigForm()
    {
        include 'config_form.php';
    }

    public function hookConfig($args)
    {
        $post = $args['post'];

        $iiif_server = $post['iiif_server'];
        $iiifhosting_customer = $post['iiifhosting_customer'];
        $iiifhosting_secure_payload = $post['iiifhosting_secure_payload'];
        $iiifhosting_ingest_api = $post['iiifhosting_ingest_api'];

        if ($iiif_server == '') {
            $iiif_server = 'http://free.iiifhosting.com/iiif';
        }

        set_option('iiifhosting_customer', $iiifhosting_customer);
        set_option('iiifhosting_secure_payload', $iiifhosting_secure_payload);
        set_option('iiifhosting_ingest_api', $iiifhosting_ingest_api);
        set_option('iiif_server', $iiif_server);

        if ($post['iiifhosting_customer'] and $post['iiifhosting_secure_payload'] and $post['iiifhosting_ingest_api']){
            $data = array(
                "email" => $iiifhosting_customer,
                "secure_payload" => $iiifhosting_secure_payload,
                "webhook_url" => "http://$_SERVER[HTTP_HOST]/iiif_ingest"
            );
            $postdata = json_encode($data);

            $ctx = stream_context_create(array(
                'http' => array(
                    'method'  => 'POST',
                    'timeout' => 5,
                    'header'  => 'Content-type: application/json\r\n',
                    'content' => $postdata
                )
            ));

            $result = @file_get_contents("https://admin.iiifhosting.com/configure_webhook/", False, $ctx);

            if($result === FALSE){
                 Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Error in communication with IIIF Hosting server.", 'error');
            }
        }
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
        echo "  <div id='detail-image'>\n";
        echo item_image('fullsize', array('style' => 'max-width: 100%;'));
        echo "  </div>\n";
        echo "  <div id='zoom-image' ></div>\n";
        echo "  <div id='zoom-image-close'></div>\n";

        $metadata = array();

        foreach ($item->Files as $file) {
            $tmp = json_decode($file->metadata, True);

            if (array_key_exists('iiif', $tmp) and $tmp['iiif'] != array()) {
                array_push($metadata, json_encode($tmp['iiif'], JSON_UNESCAPED_SLASHES));
            }
        }

        $script = " <script>\n
            var title='".metadata('item', array('Dublin Core', 'Title'))."';
            var images = [".implode(",", $metadata)."];
            </script>\n";

        echo $script;

        echo "  <script>zoom('detail-image','zoom-image', images);</script>\n";
        echo "</div>\n";

        if ($metadata) {
            echo "<div id='manifest-url'>\n";
            echo "<span>IIIF Manifest URL: </span><a href='".absolute_url("items/show/$item->id/manifest.json")."'>".absolute_url("items/show/$item->id/manifest.json")."</a>\n";
            echo "</div>\n";
        }

    }

    public function hookAfterSaveItem($args)
    {
        $item = $args['record'];
        $insert = $args['insert'];
        $post = $args['post'];

        if ($item and $insert and $post and array_key_exists('iiif_input', $post) and $post['iiif_input'] != '') {
            Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(WEB_ROOT . '/admin/items/edit/' . $item->id . '/iiif_add?iiif_input=' . urlencode($post['iiif_input']));
            #TODO
            # Explore possibility of using Zend forward, it would make this action without redirect and maybe doesn't suppress FlashMessenger with info about item itself (which is lost now)
        }
    }

    public function hookAfterSaveFile($args)
    {
        $file = $args['record'];
        $insert = $args['insert'];

        $iiifhosting_customer = get_option('iiifhosting_customer');
        $iiifhosting_secure_payload = get_option('iiifhosting_secure_payload');
        $iiifhosting_ingest_api = get_option('iiifhosting_ingest_api');

        if ($insert and $iiifhosting_customer and $iiifhosting_secure_payload and $iiifhosting_ingest_api){
            $data = array(
                "email" => $iiifhosting_customer,
                "secure_payload" => $iiifhosting_secure_payload,
                "files" => array(array(
                    "id" => $file->id,
                    "name" => $file->original_filename,
                    "url" => file_display_url($file, $format='original'),
                    "size" => $file->size
                ))
            );
            $postdata = json_encode($data);

            $ctx = stream_context_create(array(
                'http' => array(
                    'method'  => 'POST',
                    'timeout' => 5,
                    'header'  => 'Content-type: application/json\r\n',
                    'content' => $postdata
                )
            ));

            $result = @file_get_contents($iiifhosting_ingest_api, False, $ctx);

            if($result === FALSE){
                 Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Error in communication with IIIF Hosting server.", 'error');
                 Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(WEB_ROOT . '/admin/items/edit/' . $file->item_id);
            }
        }
    }

    public function hookAdminItemsPanelButtons($args)
    {
        $item = $args['record'];
        $show_verify = False;
        $add_image_button = '';

        foreach($item->Files as $file) {
            if ($file->metadata == '{"iiif":{}}') {
                $show_verify = True;
                break;
            }
        }

        echo "<div style='border-top: 1px solid #e7e7e7; border-bottom: 1px solid #e7e7e7; padding-top: 10px; margin-bottom: 10px; text-align: center;'>\n";
        echo "<h4 style='margin-bottom: 10px;'>IIIF image service</h4>\n";

        if ($show_verify) {
            echo "<a href='".url("/items/edit/".$item->id."/iiif_mapping")."' class='submit big green button'>Verify availability</a>\n";
        }

        if (current_url() != '/admin/items/add') {
            echo "<script language='javascript' type='text/javascript'>
                function AddImage() {
                    var iiif_input = document.getElementById('iiif_input').value;

                    my_form = document.createElement('FORM');
                    my_form.name = 'myForm';
                    my_form.method = 'POST';
                    my_form.action = '".url("/items/edit/".$item->id."/iiif_add")."';

                    my_tb = document.createElement('INPUT');
                    my_tb.type = 'TEXT';
                    my_tb.name = 'iiif_input';
                    my_tb.value = iiif_input;
                    my_form.appendChild(my_tb);

                    document.body.appendChild(my_form);
                    my_form.submit();
                }
            </script>\n";

            $add_image_button = "<a href='javascript:void(0)' onclick='AddImage();' class='submit big green button'>Connect image</a>";
        }

        echo "<div>
                    <p>Identifier or link to info.json</p>\n
                    <p><input id='iiif_input' type='text' name='iiif_input' size='20' class='textinput' value='' /></p>\n
                    $add_image_button
            </div>\n";
        echo "</div>\n";
    }
}
?>
