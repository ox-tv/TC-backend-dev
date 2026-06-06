<?php

namespace App\Http\Controllers;

use App\Events\Channels\ChannelImportRequestAccepted;
use App\Events\Channels\ChannelImportRequestCompleted;
use App\Events\VideoCreated;
use App\Http\Requests\ChannelImportRequest;
use App\Http\Resources\Channel\ImportRequestResource;
use App\Http\Resources\Video\VideoResource;
use App\Libraries\TCPolygonClient;
use App\Libraries\YIClient;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Language;
use App\Models\Subtitle;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\Video;
use App\Repository\Eloquent\TagRepository;
use App\Rules\CustomRule;
use App\Services\NftMembershipService;
use Done\Subtitles\Subtitles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TCPolygonController extends Controller
{
    private $nftMembershipService;
    public function __construct(NftMembershipService $nftMembershipService)
    {
        $this->nftMembershipService = $nftMembershipService;
    }

    public function nftTokenTransfered(Request $request)
    {
        $request->validate([
            'from' => ['required', CustomRule::isEthereumWalletAddress()],
            'to' => ['required', CustomRule::isEthereumWalletAddress()],
        ]);

        $this->nftMembershipService->updateHeroDataByWalletAddress($request->get('from'));
        $this->nftMembershipService->updateHeroDataByWalletAddress($request->get('to'));

        return response()->json(['status'=> 'ok']);
    }

}
