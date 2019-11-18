<?php
class ClientPolicy {
	var $serverHost;
	var $httpPort = 80;
	var $httpsPort = 443;
	var $appKey;
	var $secKey;
	var $defaultContentCharset = "UTF-8";

	public function __construct($apiKey = '', $secKey = '', $serverHost = '') {
        $this->appKey = $apiKey;
        $this->secKey = $secKey;
        $this->serverHost = $serverHost;
    }
}
?>