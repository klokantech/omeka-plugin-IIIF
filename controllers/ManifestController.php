<?php

class Iiif_ManifestController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->item = get_record_by_id('Item', $this->_getParam('id'));
    }
    
    public function manifestAction()
    {
        $CONTEXT_PRESENTATION = "http://iiif.io/api/presentation/2/context.json";
        $CONTEXT_IMAGE = "http://iiif.io/api/image/2/context.json";
        $PROFILE_IMAGE = "http://iiif.io/api/image/2/level2.json";
        $BASE_URI = record_url($this->item, 'show', true);
        $MANIFEST_URI     = $BASE_URI ."/manifest.json";
        $SEQUENCE_URI     = $BASE_URI . "/sequence.json";
        $CANVAS_BASE_URI  = $BASE_URI . "/canvas/";
        
        $canvases = array("canvases" => array());
        $counter = 0;

        foreach($this->item->Files as $file) {
            $image_metadata = json_decode($file->metadata, TRUE);

            if (!isset($image_metadata) or !array_key_exists('iiif', $image_metadata) or $image_metadata['iiif'] == array()) {
            	continue;
            }
            
            $image_width   = 0;
            $image_height  = 0;
            $base_image_url = get_option('iiif_server') . "/" . $file->filename;
            $image_url = $base_image_url . "/full/full/0/native.jpg";

            if (isset($image_metadata)) {
			    $image_width = $image_metadata['iiif']['width'];
			    $image_height = $image_metadata['iiif']['height'];
			}
  
            $images = array(
                  "@type"       => "oa:Annotation",
                  "motivation"  => "sc:painting",
                  "resource"    => array(
                        "@id"     => $image_url,
                        "@type"   => "dctypes:Image",
                        "service" => array(
                            "@context"  => $CONTEXT_IMAGE,
                            "profile"   => $PROFILE_IMAGE,
                            "@id"       => $base_image_url
                        )
                  ),
                  "on"  => $CANVAS_BASE_URI . "$counter.json"
            );
  
            $canvas = array(
                    "@id"   => $CANVAS_BASE_URI . "$counter.json",
                    "@type" => "sc:Canvas",
                    "label" => metadata($this->item, array('Dublin Core', 'Title')) . " - image $counter",
                    "width" => (int)$image_width,
                    "height" => (int)$image_height,
                    "images"  => array(),
            );
  
            $images["resource"]["width"] = (int)$image_width;
            $images["resource"]["height"] = (int)$image_height;
            
            array_push( $canvas['images'], $images );
            array_push( $canvases['canvases'], $canvas );
            
            $counter++;
        }
       
        $sequences = array(
                 "sequences" => array(
                    "@id"     => $SEQUENCE_URI,
                    "@type"   => "sc:Sequence",
                    "label"   => metadata($this->item, array('Dublin Core', 'Title')) . " - sequence 1"
                 )
        );
       
        $sequences = $sequences['sequences'] + $canvases;
       
        $manifest = array(
                "@context"  => $CONTEXT_PRESENTATION,
                "@id"       => $MANIFEST_URI,
                "@type"     => "sc:Manifest",
                "label"     => metadata($this->item, array('Dublin Core', 'Title')),
                "sequences" => array($sequences)
        );
       
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/json', true);
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setBody(json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
        $response->sendResponse();
        exit;
    }
}
