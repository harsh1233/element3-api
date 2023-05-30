<?php

namespace App\Http\Controllers\obonoApi;

use App\Http\Controllers\obonoApi\RestApiClient;

class ObonoRestApi extends RestApiClient
{
    //

    public function __construct()
    {
        parent::__construct();
    }   

     
    /**
     * Get Receipt
     *
     * @registrierkasseUuid   string    registrierkasseUuid is obono cash register unique id
     * 
     * @return json|false  Json with data or error, or False when something went fully wrong
     */
    public function obonoAuth()
    {
        $endpoint = '/auth';   
    	return $this->doRequest('','GET', $endpoint);
    }

    /**
     * Get Receipt
     *
     * @registrierkasseUuid   string    registrierkasseUuid is obono cash register unique id
     * 
     * @return json|false  Json with data or error, or False when something went fully wrong
     */
    public function getReceipts($accessToken,$registrierkasseUuid)
    {
        $query = '';
        $query .= '?format=export&order=asc';
        
    	$endpoint = '/registrierkassen/'.$registrierkasseUuid.'/belege'.$query;        
    	return $this->doRequest($accessToken,'GET', $endpoint);
    }

    /**
     * Get Single Receipt/Document Detail Based on ID from Obono cash register
     *
     * @belegUuid   string    belegUuid is receipt/document id defined by client
     *    
     * @return  json|false   Json with data or error, or False when something went fully wrong
     */
    public function getSingleReceiptDetail($accessToken,$belegUuid)
    {
       	$endpoint = '/belege/'.$belegUuid;        
    	return $this->doRequest($accessToken,'GET', $endpoint);
    }

    /**
     * Signs a receipt and stores in obono cash register
     *
     * @registrierkasseUuid   string    registrierkasseUuid is obono cash register unique id
     * @belegUuid   string    belegUuid is receipt/document id defined by client
     * 
     * @param   string      $name               Name of the chatroom
     * @return  json|false   Json with data or error, or False when something went fully wrong
     */
    public function addReceiptCashRegister($accessToken,$registrierkasseUuid,$belegUuid,$params=[])
    {
        // print_r($accessToken);
        // print_r($registrierkasseUuid);
        // print_r($belegUuid);
        // print_r($params);
        $endpoint = '/registrierkassen/'.$registrierkasseUuid.'/belege/'.$belegUuid;        
    	return $this->doRequest($accessToken,'PUT', $endpoint , $params);
    }

    /**
     * Receipt Thermal Printer Obono
     *
     * @registrierkasseUuid   string    registrierkasseUuid is obono cash register unique id
     * @belegUuid   string    belegUuid is receipt/document id defined by client
     *
     * @return  json|false   Json with data or error, or False when something went fully wrong
     */
    public function exportThermalPrintBybelegUuid($accessToken,$belegUuid)
    {
        $query = '';
        $query .= '?qr=true&width=42&dialect=escpos&encoding=base64';

        $endpoint = '/export/thermal-print/belege/'.$belegUuid.$query;        
    	return $this->doRequest($accessToken,'GET', $endpoint);
    }

     /**
     * Receipt Export Pdf Using BelegUuid From Obono
     *
     * @accessToken   string    accessToken is obono cash register auth api token for other api
     * @belegUuid   string    belegUuid is receipt/document id defined by client
     *
     * @return  json|false   Json with data or error, or False when something went fully wrong
     */
    public function exportPdfReceiptBybelegUuid($accessToken,$belegUuid)
    {
        $endpoint = '/export/pdf/belege/'.$belegUuid;        
    	return $this->doRequest($accessToken,'GET', $endpoint);
    }
    

}
