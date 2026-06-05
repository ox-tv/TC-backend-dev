<?php

use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

// Get Video Duration
if(!function_exists('get_duration')){
    /**
     * @param $filePath
     * @return mixed|null
     */
    function get_duration($filePath){
        try {
            $ffprobe = FFMpeg\FFProbe::create([
                'ffmpeg.binaries'  => config('video.ffmpeg_binaries'),
                'ffprobe.binaries' => config('video.ffprobe_binaries')
            ]);

            return $ffprobe
                ->format($filePath)->get('duration');
        }catch (\Exception $e){
            return null;
        }
    }
}

// Get Image Thumbnails
if(!function_exists('get_thumbnails')){
    /**
     * @param $filePath
     * @return mixed|null
     */
    function getThumbnails($fileUrl){
        $result = ['original' => $fileUrl];

        $sizes = config('upload.thumbnail_sizes');
        $fileName = pathinfo($fileUrl, PATHINFO_BASENAME);

        foreach ($sizes as $size){
            $key = ($size['w']?:'auto') . '_' . ($size['h']?:'auto');
            $result[$key] = str_replace($fileName, $key . '/' .$fileName, $fileUrl);
        }

        return $result;
    }
}
if(!function_exists('getR2TemporaryUrl'))
{
    function getR2TemporaryUrl($fileUrl)
    {
        return asset('media/r2-placeholder.svg');
    }
}

if(!function_exists('uploadFileToR2ByUrl'))
{
    function uploadFileToR2ByUrl($fileUrl, $destinationDirectory)
    {
        try {
            $directory = "downloadedVideosFromS3";
            Storage::makeDirectory($directory);
            $fileName = basename($fileUrl);

            $filePath = storage_path("app/{$directory}/{$fileName}");

            $response = Http::withOptions([
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.72 Safari/537.36.',
                ],
                'sink' => $filePath
            ])->get($fileUrl);

            if (!$response->ok()){
                return $fileUrl;
            }

            // Upload to R2
            $originalFilePath = Storage::disk('r2')->putFileAs($destinationDirectory, new File($filePath), $fileName);
            $url = Storage::disk('r2')->temporaryUrl($originalFilePath, now()->addDay());

            unlink($filePath);

            $s3FilePath = str_replace('https://todayscrypto-videos-storage.s3.eu-north-1.amazonaws.com/', '', $fileUrl);
            Storage::disk('s3')->delete($s3FilePath);

            return $url;

        } catch (Exception $exception){
            return $fileUrl;
        }
    }
}


// Create a URL hash
if(!function_exists('url_hash')){
    /**
     * @param $integer
     * @return string
     */
    function encode_id($integer): string
    {
        $chars = 'UZMCV8KhjPbA62MazHhVZGq8S5eFZEgGDbaQVE2fPpbZNv4SMdPYpbB8dnqhcv';

        if ($integer == 0) return $chars[0];

        $number = abs($integer);

        if ($integer < 0)
            throw new \InvalidArgumentException("Can not encode for negative integers");

        $string = '';

        $base = strlen($chars);

        while ($number > 0) {
            $string .= $chars[$number % $base];

            $number = (int) ($number / $base);
        }

        return strrev($string);
    }
}


// Create a URL hash
if(!function_exists('array_filter_by_key')){
    function array_filter_by_key($data, $allowedKeys): array
    {
        return array_filter($data, function($v, $k) use ($allowedKeys) {
            return in_array($k, $allowedKeys);
        }, ARRAY_FILTER_USE_BOTH);
    }
}
if(!function_exists('natural_intval')){
    function natural_intval($number): int
    {
        return max(intval($number), 0);
    }
}
if(!function_exists('is_json_string')){
    function is_json_string($json_str): bool
    {
        json_decode($json_str);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if(!function_exists('getClientIP')){
    function getClientIP()
    {
        $request = request();

        if( !empty( $request->header('X-TC-CLIENT-IP') ) ){
            return $request->header('X-TC-CLIENT-IP');
        }elseif( !empty( $request->server('HTTP_CF_CONNECTING_IP') ) ){
            return $request->server('HTTP_CF_CONNECTING_IP');
        }else{
            return $request->getClientIp();
        }
    }
}

if(!function_exists('sendImporterStatusToSlack')){
    function sendImporterStatusToSlack($text)
    {
        $channelName = 'importer-status';
        $url = env('SLACK_IMPORTER_HOOK_URL');

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "channel" => $channelName,
                "text" => $text,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
            ],
        ]);
        curl_exec($curl);
        curl_close($curl);
    }
}