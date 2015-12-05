<?php

class Iiif_MappingController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->item = get_record_by_id('Item', $this->_getParam('id'));
    }
    
    public function mappingAction()
    {
        foreach($this->item->Files as $file) {
            $image_metadata = json_decode($file->metadata, True);

            if (isset($image_metadata) and array_key_exists('iiif', $image_metadata) and $image_metadata['iiif'] == array()) {
	            $base_image_url = get_option('iiif_server') . "/" . preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->filename) . "/info.json";

	            $ctx = stream_context_create(array( 
					'http' => array( 
						'timeout' => 5 
						) 
					) 
				);

		        $result = @file_get_contents($base_image_url, False, $ctx);
		        
		        if($result === FALSE){
			        Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Image at $base_image_url isn't available.", 'error');
			    } else {
			    	$file->metadata = '{"iiif":' . $result . '}';
			    	Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Image at $base_image_url has been added.", 'success');
			    	$file->save();
			    }
	        }
        }
        
        Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(WEB_ROOT . '/admin/items/show/id/' . $this->item->id);
    }
    
    public function addAction()
    {
    	if($this->getRequest()->isPost()) {
    		$image_name = $this->getRequest()->getPost('iiif_input');
    		$base_image_url = get_option('iiif_server') . "/" . $image_name . "/info.json";
    		
    		$ctx = stream_context_create(array( 
				'http' => array( 
					'timeout' => 5 
					) 
				) 
			);
			
			$result = @file_get_contents($base_image_url, False, $ctx);
			
			if($result === FALSE){
			     Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Image at $base_image_url isn't available.", 'error');
			} else {
				$file = new File();
			   	$file->metadata = '{"iiif":' . $result . '}';
			   	$file->item_id = $this->item->id;
			   	$file->mime_type = 'image/jpeg';
			   	$file->filename = $image_name;
			   	$file->original_filename = $image_name;
			   	$file->size = 0;
			   	$file->stored = 0;
	    		$file->has_derivative_image = 1;
			   	
			   	Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("Image at $base_image_url has been added.", 'success');
			   	//$file->save();
			   	
			   	$db = get_db();
			   	$sql = "INSERT INTO $db->FILE (item_id, size, has_derivative_image, mime_type, filename, original_filename, stored, metadata, added) VALUES ($file->item_id, $file->size, $file->has_derivative_image, '$file->mime_type', '$file->filename', '$file->original_filename', $file->stored, '$file->metadata', CURRENT_TIMESTAMP())";

			   	$db->query($sql);
			}
    	}
    	
    	Zend_Controller_Action_HelperBroker::getStaticHelper('redirector')->gotoUrl(WEB_ROOT . '/admin/items/show/id/' . $this->item->id);
    }
}
