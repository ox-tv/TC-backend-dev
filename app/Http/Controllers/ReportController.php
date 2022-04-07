<?php

namespace App\Http\Controllers;

use App\Events\Report\ReportCreated;
use App\Http\Resources\Comment\CommentItem;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\Video\VideoResource;
use App\Models\Comment;
use App\Models\Option;
use App\Models\Report;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $is_video = $request->is("api/admin/reports/video");
        $is_comment = $request->is("api/admin/reports/comment");

        if ($is_video){
            $query = Video::query();
        }

        if ($is_comment){
            $query = Comment::query();
        }

        // filters
        $filters = $request->get('filters', []);
        $reasonFilter = Arr::get($filters, 'reason');

        $query->whereHas("reports", function ($q) use ($reasonFilter){
            return $q->when($reasonFilter, function($q, $reasonFilter){
                $q->where('reason_key', $reasonFilter);
            });
        });

        $query->withCount('reports')->orderBy('reports_count', 'desc');

        $result =$query->paginate();

        if ($is_video){
            $result->load(['user', 'channel'])->append(['reports_count']);
            //return \App\Http\Resources\Video\VideoItem::collection($result);
            return VideoResource::collection($result);
        }

        if ($is_comment){
            $result->load(['user', 'video']);
            return CommentItem::collection($result);
        }
    }

    public function index_reports(Request $request, $id)
    {
        $is_video = $request->is("api/admin/reports/video/{$id}");
        $is_comment = $request->is("api/admin/reports/comment/{$id}");

        $query = Report::where("reportable_id", $id);

        if ($is_video){
            $query->video();
        }

        if ($is_comment){
            $query->comment();
        }

        $filters = $request->get('filters', []);

        $userFilter = Arr::get($filters, 'user_id');
        $reasonFilter = Arr::get($filters, 'reason');

        if(!empty($userFilter)){
            $query->whereIn("user_id", (array) $userFilter);
        }

        if(!empty($reasonFilter)){
            $query->whereIn("reason_key", (array) $reasonFilter);
        }

        return ReportMinimalItem::collection($query->paginate());
    }

    public function store(Request $request, $idOrUrlHash)
    {
        $report = new Report();

        //$report->reason = $request->get("reason");
        $report->user_id = auth('api')->id();


        if ($request->is("api/videos/{$idOrUrlHash}/report")){
            $option_key = Option::VIDEO_REPORT_REASONS;
            $model = Video::published()->where('id', $idOrUrlHash)->orWhere('url_hash', $idOrUrlHash)->firstOrFail();
            $report->reported_user_id = $model->user_id;
        }

        if ($request->is("api/comments/{$idOrUrlHash}/report")){
            $option_key = Option::COMMENT_REPORT_REASONS;
            $model = Comment::findOrFail($idOrUrlHash);
            $report->reported_user_id = $model->user_id;
        }

        $reasons = json_decode(Option::where("key", $option_key)->first()->value, true) ?? abort(404);

        if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
            $report->reason_key = $request->get('reason');
            $report->reason_text = $reasons[$key]['value'];
        }else{
            $report->reason_key = 'other';
            $report->reason_text = $request->get('reason');
        }

        if($model->reports()->where('user_id', auth('api')->id())->exists()){
            return response()->json(['message' => 'reports.already_submitted'],400);
        }

        $model->reports()->save($report);

        event(new ReportCreated($report, $model));

        return ReportItem::make($report);
    }
}
