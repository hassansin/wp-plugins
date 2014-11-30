<?php

class API_Yumpu{
	private $access_token;
	private $endpoint = 'http://api.yumpu.com/2.0';
	public $errMsg;
	public $HTTPcode;
	private $response;

	function __construct($access_token){
		$this->access_token = $access_token;
	}

	function createUser($username,$email,$fname,$lname,$gender){
		$data= array(
			'access_token'=> $this->access_token,
			'method'=>'create_user',
			'username'=>$username,
			'email'=>$email,
			'gender'=>$gender,
			'firstname'=>$fname,
			'lastname'=>$lname
			);
		$data=$this->prepareData($data);
		$this->post($data);
	}
	function createMegazine($url,$title='',$desc=''){
		$data= array(
			'access_token'=> $this->access_token,
			'method'=>'create_magazine',
			'url'=>$url,					
			);
		if($title)			
			$data = array_merge($data, array('title'=>$title));
		if($desc)
			$data = array_merge($data, array('description'=>$desc));

		$this->post(json_encode($data));
	}
	function testAPI(){
		$this->createMegazine('');
	}
	function getResponse(){
		return $this->response;
	}

	private function post($data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
		    array(
		        'Content-Type: application/json; charset=utf-8',
		        'Content-Length: '.strlen($data)
		    )
		);

		curl_setopt($ch, CURLOPT_URL,$this->endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		$this->HTTPcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		if($error = curl_error($ch))
			$this->errMsg=$error;
		curl_close($ch);
		$this->response = json_decode($response);

	}

	private function prepareData($val){
		if(is_array($val)){
			foreach ($val as $key => $value) {
				$val[$key]=utf8_encode($value);
			}
		}
		else 
			$val=utf8_encode($val);
		return json_encode($val);
	}
}

?>