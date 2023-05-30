<?php

namespace App\Http\Controllers\obonoApi;

use GuzzleHttp\Client;
use File;

/**
* Obono Rest API Client For Do Request
*/
class RestApiClient
{
    //
    public $host = 'localhost';
    public $port = '9090';
    public $plugin = 'app.obono.at/api/v1';
    public $secret = 'Basic dGVzdDExMUB5b3BtYWlsLmNvbTpRS1ppZ251dHMyMDIw';
    public $useSSL = true;
    protected $params  = array();
    private $client;
    public $bcastRoles = array();
    public $useBasicAuth = false;
    public $basicUser = 'test111@yopmail.com';
    public $basicPwd = 'QKZignuts2020';
	function __construct()
	{
		$this->client = new Client();
    }
    
     /**
     * Make the request and analyze the result
     *
     * @param   string          $type           Request method
     * @param   string          $endpoint       Api request endpoint
     * @param   array           $params         Parameters
     * @return  array|false                     Array with data or error, or False when something went fully wrong
     */
	protected function doRequest($token=null, $type, $endpoint, $params=[])
    {
    	$base = ($this->useSSL) ? "https" : "http";
        //$url = $base . "://" . $this->host . ":" .$this->port.$this->plugin.$endpoint;
        $url = $base . "://" .$this->plugin.$endpoint;
     
        $url_data = explode('/',$endpoint);

        if ($token)
             $auth = 'Bearer ' .$token;
        else if ($this->useBasicAuth)
            $auth = 'Basic ' . base64_encode($this->basicUser . ':' . $this->basicPwd);
        else
            $auth = $this->secret;
	    
    	$headers = array(
  			'Accept' => 'application/json',
  			'Authorization' => $auth,
            'Content-Type'=>'application/json'
  		);
        $body = json_encode($params);
        try {
        	$result = $this->client->request($type, $url, compact('headers','body'));
        } catch (\Exception $e) {
        	return  ['status'=>false, 'data'=>['message'=>$e->getMessage()]];
        }
        
        if($url_data){
            if($url_data[1]=='export'){
                // $path = 'C:/Users/Parth/Downloads/'.$url_data[4].'_invoice.pdf';
                // $file_path = fopen($path,'w');
                // $response = $this->client->get($url, ['save_to' => $file_path]);
                //return array('status'=>true, 'data'=>json_decode($result->getBody()), "url" => $url);
                if(File::exists(public_path('invoice.pdf'))){
                    File::delete(public_path('invoice.pdf'));
                }
                 //unlink(public_path('invoice.pdf'));
                 $path =  public_path().'/invoice.pdf';
                 $file_path = fopen($path,'w');
                 $response = $this->client->get($url, ['save_to' => $file_path]);
                 $public_url=url('invoice.pdf');
                return array('status'=>true, 'data'=>json_decode($result->getBody()), "url" => $url,"urlpath"=>$public_url);
                
            }
        }

        if ($result->getStatusCode() == 200 || $result->getStatusCode() == 201) {
            return array('status'=>true, 'data'=>json_decode($result->getBody()));
        }
        return array('status'=>false, 'data'=>json_decode($result->getBody()));
    	
    }
}
