<?php
/**
 * Omeka
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Standard local filesystem storage adapter.
 *
 * The default adapter; this stores files in the Omeka files directory by 
 * default, but can be set to point to a different path.
 * 
 * @package Omeka\Storage\Adapter
 */
class Iiif_Storage_Adapter_Iiif extends Omeka_Storage_Adapter_Filesystem
{

    public function setUp()
    {
        // Required by interface but does nothing, for the time being.
    }

    /**
     * Check whether the adapter is set up correctly to be able to store
     * files.
     *
     * Specifically, this checks to see if the local storage directory
     * is writable.
     *
     * @return boolean
     */
    public function canStore()
    {
        return false;
    }

    /**
     * Move a local file to "storage."
     *
     * @param string $source Local filesystem path to file.
     * @param string $dest Destination path.
     */
    public function store($source, $dest)
    {
        // Required by interface but does nothing, for the time being.
    }

    /**
     * Move a file between two "storage" locations.
     *
     * @param string $source Original stored path.
     * @param string $dest Destination stored path.
     */
    public function move($source, $dest)
    {
        // Required by interface but does nothing, for the time being.
    }

    /**
     * Remove a "stored" file.
     *
     * @param string $path
     */
    public function delete($path)
    {
        // Required by interface but does nothing, for the time being.
    }

    /**
     * Get a URI for a "stored" file.
     *
     * @param string $path
     * @return string URI
     */
    public function getUri($path)
    {
        list( $size, $filename ) = explode('/', $path, 2);
        
        $db = get_db();
   		$select = $db->select()->from(array("f" => $db->File), array('metadata'));
   		$select->where('f.filename = ?', $filename);
   		$metadata = $db->getTable('File')->fetchOne($select);

        if ($metadata == '{"iiif":{}}') {
        	return '/plugins/Iiif/views/public/img/placeholder.png';
        }
        
        $metadata = json_decode($metadata, True);

        if (isset($metadata) and array_key_exists('iiif', $metadata) and array_key_exists('@id', $metadata['iiif'])) {
        	$base_image_url = $metadata['iiif']['@id'];
        } else {
        	# Fallback to Omeka_Storage_Adapter_Filesystem method
        	return $this->_webDir . '/' . $path;
        }
               
		$square_thumbnail_size = get_option('square_thumbnail_constraint');
		$thumbnail_size = get_option('thumbnail_constraint');
		$fullsize_size = get_option('fullsize_constraint');

		$mapping = array(
			'square_thumbnails' => "$square_thumbnail_size,$square_thumbnail_size",
			'thumbnails' => "!$thumbnail_size,$thumbnail_size",
			'fullsize' => "!$fullsize_size,$fullsize_size",
			'original' => 'full'
		);
		
		if($size == 'square_thumbnails') $region = 'square';
		else $region = 'full';
			
		return $base_image_url . "/$region/" . $mapping[$size] . "/0/native.jpg";
    }
}
