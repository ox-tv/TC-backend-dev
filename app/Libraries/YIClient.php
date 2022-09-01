<?php


namespace App\Libraries;


use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YIClient
{
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config("yi.base_url");
    }

    public function getImportStats($channelId)
    {
        try {
            $response = Http::get("{$this->apiUrl}/api/{$channelId}/import-stats");

            $body = $response->json();

            if(!$response->successful()){
                throw new Exception($body, $response->status());
            }

            $result['success'] = true;
            $result['data'] = $body;
            return $result;

        }catch(Exception $e){
            $result['success'] = false;
            $result['http_code'] = $e->getCode();
            $result['message'] = $e->getMessage();
            Log::error(print_r($result, true));
            return $result;
        }
    }
}