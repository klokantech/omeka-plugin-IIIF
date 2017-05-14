<?php

class Iiif_IngestController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->_helper->db->setDefaultModelName('File');
    }

    public function ingestAction()
    {
        if($this->getRequest()->isPost()) {
            $rawBody = $this->getRequest()->getRawBody();

            if (!$rawBody) {
                return;
            }

            $data = json_decode($rawBody);

            $base_image_url = $data->url . "/info.json";

            $ctx = stream_context_create(array(
                'http' => array(
                    'timeout' => 5
                    )
                )
            );

            $result = @file_get_contents($base_image_url, False, $ctx);

            if($result !== FALSE){
                $metadata = '{"iiif":' . $result . '}';

                $db = get_db();
                $sql = "UPDATE $db->FILE SET `metadata`='$metadata' WHERE id = $data->external_id";

                $db->query($sql);
            }

            $response = $this->getResponse();
            $response->setBody('');
            $response->sendResponse();
            exit;
        }
    }
}
