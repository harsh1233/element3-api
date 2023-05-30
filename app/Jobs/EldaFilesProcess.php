<?php

namespace App\Jobs;

use SimpleXMLElement;
use App\Models\EldaDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\EldaFuctions;
use App\Models\EldaFTPProcoessDetails;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EldaFilesProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, EldaFuctions;

    public $gkk_name;
    public $id;
    public $type;
    public $base_path;
    public $gkk_url;
    public $status;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name, $id, $type, $base_path, $gkk_url, $status)
    {
        $this->gkk_name = $name;
        $this->id = $id;
        $this->type = $type;
        $this->base_path = $base_path;
        $this->gkk_url = $gkk_url;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Elda files process job execution started!.");
            $elda_detail = EldaDetail::where('contact_id', $this->id)->where('status', $this->status)->with('contact_detail')->latest()->first();

            $input_details['gkk_file'] = $this->gkk_url;
            $input_details['process_name'] = $this->type;
            $input_details['contact_id'] = $this->id;
            $input_details['elda_insurance_number'] = $elda_detail->elda_insurance_number;
            $input_details['elda_insurance_reference_number'] = $elda_detail->elda_insurance_reference_number;

            /**Uplaod file on FTP threw queue php function */
            clearstatcache();
            $ftpHost   = "ftps-test.elda.at";
            $ftpUsername = "157499";
            $ftpPassword = "f255322bcdac69e8641ff334e68f2c984aa9d10fd1a68a0010a69a901ed80783320aa7084938e661773dece7d0d944a81af443d4255288926f90c92e222edafd";

            // open an FTP connection
            // Log::info("FTP details = ftpHost: ".$ftpHost." ftpUsername: ".$ftpUsername." ftpPassword: ".$ftpPassword);

            $connId = @ftp_ssl_connect($ftpHost) or Log::info("Couldn't connect to".$ftpHost);

            // login to FTP
            $login_result = @ftp_login($connId, $ftpUsername, $ftpPassword);

            //Check FTP connection is establized or not
            if ((!$connId) || (!$login_result)) {
                Log::info("FTP Connection Failed");
                return;
            }

            @ftp_pasv($connId, true) or Log::info("Unable switch to passive mode");

            // local & server file path
            $localDirFilePath = $this->gkk_name;

            // Uploading a GKK file in server
            @ftp_put($connId, '/' . $this->gkk_name, $localDirFilePath, FTP_ASCII);

            sleep(15);

            // Downloading delta.ret file from server
            $file_name = 'elda_'.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s')).'.ret';
            @ftp_get($connId, $file_name, 'elda.ret', FTP_BINARY);

            $url = $this->uploadEldaFile($this->base_path, $file_name, $file_name);
            $input_details['ret_file'] = $url;

            //Returns cuurent directory name
            $cuurent_direcotry = @ftp_pwd($connId);

            //Returns an array of arrays with file infos from the directory
            $files = @ftp_mlsd($connId, ".");

            if (is_array($files)) {
                $count = count($files);
                for ($i = 0; $i < $count; $i++) {
                    $data = @ftp_get($connId, $files[$i]['name'], $files[$i]['name'], FTP_BINARY);
                    /**Function for Upload GKK file into s3 AWS storage */
                    $url = $this->uploadEldaFile($this->base_path, $files[$i]['name'], $files[$i]['name']);
                    $ext = pathinfo($url, PATHINFO_EXTENSION);
                    if($ext === 'xml'){
                        $input_details['xml_file'] = $url;

                        /**Get xml elda details */
                        $xml_data = new SimpleXMLElement($url, null, true);
                        if($xml_data){
                            if(isset($xml_data->status)){
                                $input_details['status'] = $xml_data->status;
                            }
                            $part = $xml_data->xpath("//ns1:code");
                            if($part){
                                if(isset($part[0]->elda_text)){
                                    $input_details['xml_elda_text'] = $part[0]->elda_text;
                                }
                            }
                        }
                        /**End */
                    }else{
                        $input_details['output_txtfile'] = $url;
                    }
                    unlink($files[$i]['name']);
                }
            }
            /**Unlink local temp files */
            unlink(public_path($this->gkk_name));
            unlink($file_name);

            // close FTP connection
            @ftp_close($connId);
            /**End */
            EldaFTPProcoessDetails::create($input_details);

        Log::info("Elda files process job successfully executed!.");
    }
}
