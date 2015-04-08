<?php

/**
 * Allows applications to access user's Slack information by using Slack's OAuth functionality.
 */
class SlackOAuth{
	private $client_secret;
	protected $slack_endpoint	 = "https://slack.com/";
	protected $auth				 = [
		'client_id'	 => null,
		'state'		 => null,
		'scope'		 => null,
		'team'		 => null,
		'redirect'	 => null
	];
	/**
	 *
	 * @param String $client_id Client ID as given by registration of the Slack application.
	 * @param String $secret Client Secret as given by registration of the Slack application.
	 * @param Array $scope Array of states to request. Identify, Read, Post, Client, Admin
	 * @param String $state Unique string to be passed back with Slack's redirect for verification.
	 * @param String $team Team ID to request authorization.
	 * @param String $redirect URL for Slack to redirect after authorization.
	 */
	public function __construct($client_id, $secret, $scope = [], $state = '', $team = '', $redirect = ''){
		$this->client_secret	 = $secret;
		$this->auth['client_id'] = $client_id;
		$this->auth['state']	 = $state;
		$this->auth['scope']	 = implode(",", $scope);
		$this->auth['team']		 = $team;
		$this->auth['redirect']	 = $redirect;
	}
	/**
	 * Creates a URL to authenticate the application.
	 * @param String $redirect URL for Slack to redirect after confirmation.
	 * @return String Slack URL: OAuth/Authorize with Client ID and possible State, Scope, Team, and Redirect fields.
	 */
	public function auth_url($redirect = ''){
		if(session_status()==PHP_SESSION_NONE){
			session_start();
		}
		if(empty($this->auth['state'])){
			$this->auth['state'] = $this->rand_str();
		}
		if(!empty($redirect)){
			$this->auth['redirect'] = $redirect;
		}
		$_SESSION['state']	 = $this->auth['state'];
		$url				 = $this->slack_endpoint.'oauth/authorize?'.http_build_query(array_filter($this->auth), '', '&');
		return $url;
	}
	/**
	 * Exchanges an OAuth code for an API access token. Forms a payload to send to the Slack OAuth/Access API call.
	 * @param String $code The code returned from Slack's redirect OAuth/Authorize.
	 * @return Object Generic object that is the JSON decoded string returned from the payload.
	 * @throws Exception If the Object->ok property is false, will throw with the response's error.
	 */
	public function auth_access($code){
		$payload							 = [];
		$payload['url']						 = $this->slack_endpoint.'api/oauth.access';
		$payload['post']['client_id']		 = $this->auth['client_id'];
		$payload['post']['client_secret']	 = $this->client_secret;
		$payload['post']['code']			 = $code;
		$payload['post']['redirect_uri']	 = $this->auth['redirect'];
		$response							 = json_decode($this->_post($payload));
		if(!$response->ok){
			throw new Exception('OAuth.Access: '.$response->error);
		}
		return $response;
	}
	/**
	 * Computes a pseudo-random string.
	 * @return String Pseudo-random string.
	 */
	public function rand_str(){
		return md5(uniqid(rand(), true));
	}
	/**
	 * Posts a payload to a URL.
	 * @param Array $payload The payload to be posted.
	 * @return String Response of payload. (From CURLOPT_RETURNTRANSFER)
	 */
	private function _post($payload){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $payload['url']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if(isset($payload['post'])){
			curl_setopt($ch, CURLOPT_POST, count($payload['post']));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload['post']));
		}
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
}
