<?php

namespace App\Http\Controllers;


use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class S3Controller extends Controller
{
    public function getPreSignedURLForUploadVideo(Request $request, $channelIdOrSlug = null)
    {
        if ($request->is('api/admin/*')){
            $channel = Channel::where('id', $channelIdOrSlug)->orWhere('slug', $channelIdOrSlug)->firstOrFail();
        }else{
            $user = auth('api')->user();
            $channel = $user->channel()->firstOrfail();
        }

        $s3 = \Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+10 minutes";

        $command = $client->getCommand('PutObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key'    => "channel/{$channel->id}/videos",
            'ACL' => 'public-read',
        ]);

        $req = $client->createPresignedRequest($command, $expiry);

        return response()->json(['url' => (string) $req->getUri()]);
    }
}
