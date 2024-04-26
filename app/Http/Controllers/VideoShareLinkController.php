<?php

namespace App\Http\Controllers;



use App\Http\Resources\Video\VideoShareLinkResource;
use App\Models\TokenPoint;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoShareLink;
use App\Models\VideoShareLinkStatistics;
use App\Repository\Eloquent\TokenPointRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VideoShareLinkController extends Controller
{
    public function increaseShareLinkCount(Request $request,$idOrUrlHash)
    {
        $video = Video::published()
            ->where('id', $idOrUrlHash)
            ->orWhere('url_hash', $idOrUrlHash)
            ->firstOrFail();
        $user = auth('api')->user();

        $model = VideoShareLink::where('video_id', $video->id)
            ->where('user_id', $user->id)
            ->firstOr(function () use ($video, $user) {
                $model = new VideoShareLink();
                $model->video_id = $video->id;
                $model->user_id = $user->id;
                $model->count = 0;
                return $model;
            });

        $model->count++;
        $model->save();

        return response()->json(["message" => "ok"]);
    }

    public function increaseShareLinkView(Request $request, $idOrUrlHash)
    {
        $request->validate([
            'referrer' => ['required', Rule::exists('users','referral_code')]
        ]);

        $video = Video::published()
            ->where('id', $idOrUrlHash)
            ->orWhere('url_hash', $idOrUrlHash)
            ->firstOrFail();

        $referrer = User::where('referral_code', $request->get('referrer'))->first();
        $user = auth('api')->user();

        $points = config('points.token.view_video_via_share_link');
        $type = TokenPoint::TYPE_VIEW_VIDEO_VIA_SHARE_LINK;
        $ipAddress = getClientIP();

        $q = VideoShareLinkStatistics::where('video_id', $video->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30));

        if ($user && $q->where('user_id', $user->id)->exists()){
            return response()->json(["message" => "already exist"]);
        }elseif(!$user && $q->where('ip_address', $ipAddress)->count() >= 2 ){
            return response()->json(["message" => "ok"]);
        }

        $model = new VideoShareLinkStatistics();
        $user && $model->user_id = $user->id;
        $model->ip_address = $ipAddress;
        $model->video_id = $video->id;
        $model->referrer_id = $referrer->id;
        $model->save();

        $tokenPointRepository = new TokenPointRepository();
        $tokenPointRepository->add([
            'user_id' => $referrer->id,
            'type' => $type,
            'amount' => $points,
        ]);

        return response()->json(["message" => "ok"]);
    }

    public function shareLinkVideos()
    {
        $links = VideoShareLink::where('user_id', auth('api')->id())->paginate();

        $links->load('video');
        $links->append(['totalViews']);

        return VideoShareLinkResource::collection($links);
    }

    public function shareLinkStatistics()
    {
        $result = [];
        $userId = auth('api')->id();

        $result['total_views'] = VideoShareLinkStatistics::where('referrer_id', $userId)
            ->count();

        $result['total_tcg_earning'] = TokenPoint::where('user_id', $userId)
            ->where('type', TokenPoint::TYPE_VIEW_VIDEO_VIA_SHARE_LINK)
            ->sum('amount');

        return response()->json($result);
    }
}
