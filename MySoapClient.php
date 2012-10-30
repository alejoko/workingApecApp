<?php

class MySoapClient extends SoapClient {

	function __construct($wsdl, $options) {
		parent::__construct($wsdl, $options);
	}
	public function __doRequest($request, $location, $action, $version) 
	{ 
		$result = parent::__doRequest($request, $location, $action, $version); 
		return $result; 
	} 
	function __myDoRequest($array, $method) { 
		$request = $array;
                $location = WSDL_METHODS; 
                $action = $method;
		$version = '1';
		$result =$this->__doRequest($request, $location, $action, $version);
		return $result;
	} 
}

?>