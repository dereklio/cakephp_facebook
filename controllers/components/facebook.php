<?php

class FacebookComponent extends Object {
	
	var $_oauthUrl = 'https://graph.facebook.com/oauth/';
	var $_objectUrl = 'https://graph.facebook.com/%2$s?access_token=%1$s';
	var $_relationshipUrl = 'https://graph.facebook.com/%2$s/%3$s?access_token=%1$s';
	
	var $_sessionKey = 'facebook_access_token';
	
	function initialize(&$c, $config = array()) {
		$this->config = $config;
	}
	
	function _oauthUrl($action, $params) {
		$querystring = a();
		foreach ($params as $key => $value) {
			$querystring[] = sprintf('%s=%s', $key, urlencode($value));
		}
		return sprintf('%s%s?%s', $this->_oauthUrl, $action, implode('&', $querystring));
	}
	
	function _parseQueryString($querystring) {
		$data = a();
		foreach (explode('&', $querystring) as $tmp) {
			list($key, $value) = explode('=', $tmp, 2);
			$data[$key] = urldecode($value);
		}
		return $data;
	}
	
	function _getCurrentUrl($params) {
		foreach (a('url', 'form') as $field) unset($params[$field]);
		return Router::url($params, true);
	}
	
	function getAccessToken() {
		return $_SESSION[$this->_sessionKey];
	}
	
	function authorize() {
		$params = Router::getParams();
		$redirect_uri = $this->_getCurrentUrl($params);
		extract($this->config);
		
		switch (true) {
			case !empty($_SESSION[$this->_sessionKey]):
				return true;
			case !empty($params['url']['code']):
				$code = $params['url']['code'];
				$token = $this->_parseQueryString(file_get_contents($this->_oauthUrl('access_token', compact('client_id', 'client_secret', 'code', 'redirect_uri'))));
				$_SESSION[$this->_sessionKey] = $token['access_token'];
				header('Location: ' . $redirect_uri);
				exit();
			case !empty($params['url']['error']):
				return false;
			default:
				header('Location: ' . $this->_oauthUrl('authorize', compact('client_id', 'redirect_uri')));
				exit();
		}
	}
	
	function lookup($objectId, $relationship = null) {
		if (empty($relationship)) {
			$url = sprintf($this->_objectUrl, urlencode($this->getAccessToken()), urlencode($objectId));
		} else {
			$url = sprintf($this->_relationshipUrl, urlencode($this->getAccessToken()), urlencode($objectId), urlencode($relationship));
		}
		$json = file_get_contents($url);
		$json = preg_replace('/"id":([0-9]+),/', '"id":"$1",', $json);
		return json_decode($json, true);
	}
	
}

?>