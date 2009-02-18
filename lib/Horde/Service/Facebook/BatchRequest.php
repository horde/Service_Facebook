<?php
/**
 * Horde_Service_Facebook_BatchRequest::
 *
 *
 */
class Horde_Service_Facebook_BatchRequest extends Horde_Service_Facebook_Request
{
    private $_queue = array();
    private $_batchMode = Horde_Service_Facebook::BATCH_MODE_DEFAULT;

    public function __construct($facebook, $http_client, $params = array())
    {
        $this->_http = $http_client;
        $this->_facebook = $facebook;

        if (!empty($params['batch_mode'])) {
            $this->_batchMode = $params['batch_mode'];
        }
    }

    /**
     * Add a method call to the queue
     *
     * @param $method
     * @param $params
     * @return unknown_type
     */
    public function &add($method, $params)
    {
        $result = null;
        $batch_item = array('m' => $method, 'p' => $params, 'r' => &$result);
        $this->_queue[] = $batch_item;
        return $result;
    }

    /**
     * Execute a set of batch operations.
     *
     * @return void
     */
    public function run()
    {
        $item_count = count($this->_queue);
        $method_feed = array();
        foreach ($this->_queue as $batch_item) {
            $method = $batch_item['m'];
            $params = $batch_item['p'];
            $this->_finalize_params($method, $params);
            $method_feed[] = $this->_create_post_string($params);
        }
        $method_feed_json = json_encode($method_feed);

        $serial_only = ($this->_batchMode == Horde_Service_Facebook::BATCH_MODE_SERIAL_ONLY);
        $params = array('method_feed' => $method_feed_json,
                        'serial_only' => $serial_only);
        $json = $this->_post_request('batch.run', $params);
        $result = json_decode($json, true);
        if (is_array($result) && isset($result['error_code'])) {
          throw new Horde_Service_Facebook_Exception($result['error_msg'],
                                                     $result['error_code']);
        }

        for ($i = 0; $i < $item_count; $i++) {
            $batch_item = $this->_queue[$i];
            $batch_item_json = $result[$i];
            $batch_item_result = json_decode($batch_item_json, true);
            if (is_array($batch_item_result) &&
                isset($batch_item_result['error_code'])) {

                throw new Horde_Service_Facebook_Exception($batch_item_result['error_msg'],
                                                           $batch_item_result['error_code']);
            }
            $batch_item['r'] = $batch_item_result;
        }
    }

}