<?php

namespace App\Http\Controllers;


use App\Models\Channel;
use Illuminate\Http\Request;
use Storage;

class S3Controller extends Controller
{
    public function getPreSignedURLForUploadVideos(Request $request, $channelIdOrSlug = null): \Illuminate\Http\JsonResponse
    {
        $preSignUrlType = config('upload.presign_url_type');

        if ($preSignUrlType == 'r2'){
            return $this->getPreSignedURLForR2($request, $channelIdOrSlug);
        }else{
            return $this->getPreSignedURLForS3($request, $channelIdOrSlug);
        }
    }

    public function getPreSignedURLForS3(Request $request, $channelIdOrSlug = null)
    {
        $request->validate([
            'file_name' => 'required',
        ]);

        $fileNameArray = explode('.', $request->get('file_name'));
        $extention = end($fileNameArray);
        $fileName = encode_id(str_pad(time(),10,0,STR_PAD_RIGHT));

        if ($request->is('api/admin/*')){
            $channel = Channel::where('id', $channelIdOrSlug)->orWhere('slug', $channelIdOrSlug)->firstOrFail();
        }else{
            $user = auth('api')->user();
            $channel = $user->channel()->firstOrfail();
        }

        $s3 = \Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+1 hour";

        $command = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key'    => "channel/{$channel->id}/videos/{$fileName}.{$extention}",
            'ACL' => 'public-read',
        ]);

        $req = $client->createPresignedRequest($command, $expiry);

        return response()->json(['url' => (string) $req->getUri()]);
    }

    public function getPreSignedURLForR2(Request $request, $channelIdOrSlug = null)
    {
        $request->validate([
            'file_name' => 'required',
        ]);

        $fileNameArray = explode('.', $request->get('file_name'));
        $extention = end($fileNameArray);
        $fileName = encode_id(str_pad(time(),10,0,STR_PAD_RIGHT));

        if ($request->is('api/admin/*')){
            $channel = Channel::where('id', $channelIdOrSlug)->orWhere('slug', $channelIdOrSlug)->firstOrFail();
        }else{
            $user = auth('api')->user();
            $channel = $user->channel()->firstOrfail();
        }

        $s3 = Storage::disk('r2');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+1 hour";

        $command = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.r2.account_id'),
            'Key'    => config('filesystems.disks.r2.bucket') . "/channel/{$channel->id}/videos/{$fileName}.{$extention}",
        ]);

        $req = $client->createPresignedRequest($command, $expiry);

        return response()->json(['url' => (string) $req->getUri()]);
    }
}
