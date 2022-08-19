<?php

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
if(!function_exists('getR2TempraroryUrl')){
    /**
     * @param $filePath
     * @return mixed|null
     */
    function getR2TempraroryUrl($fileUrl){
        $s3 = Storage::disk('r2');
        $config = config('filesystems.disks.r2');

        $r2EndPoint = str_replace('//r2', "//{$config['account_id']}.r2", $config['endpoint']) . $config['bucket'] . '/';

        $filePath = str_replace($r2EndPoint, '', $fileUrl);
        return $s3->temporaryUrl($filePath, now()->addDay());
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