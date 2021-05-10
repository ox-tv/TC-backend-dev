<?php

namespace App\Http\Controllers;

use App\Http\Resources\Comment\CommentItem;
use App\Http\Resources\CommentSummaryItem;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\VideoItem;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $is_video = $request->is("api/admin/reports/video");
        $is_comment = $request->is("api/admin/reports/comment");

        if ($is_video){
            $query = Video::with(["user", "channels"]);
        }

        if ($is_comment){
            $query = Comment::with(["user", "video"]);
        }

        $query->whereHas("reports");

        $query->withCount('reports')->orderBy('reports_count', 'desc');

        $result =$query->paginate();

        if ($is_video){
            return \App\Http\Resources\Video\VideoItem::collection($result);
        }

        if ($is_comment){
            return CommentItem::collection($result);
        }
    }

    public function index2(Request $request)
    {
        $query = Report::query();

        if ($request->is("api/admin/reports/video")){
            $query->video();
        }

        if ($request->is("api/admin/reports/channel")){
            $query->channel();
        }

        if ($request->is("api/admin/reports/comment")){
            $query->comment();
        }

        $filters = $request->get('filters', []);

        $userFilter = Arr::get($filters, 'user_id');
        $reportedUserFilter = Arr::get($filters, 'reported_user_id');
        $reasonFilter = Arr::get($filters, 'reason');

        if(!empty($userFilter)){
            $query->whereIn("user_id", (array) $userFilter);
        }

        if(!empty($reportedUserFilter)){
            $query->whereIn("reported_user_id", (array) $reportedUserFilter);
        }

        if(!empty($reasonFilter)){
            $query->whereIn("reason", (array) $reasonFilter);
        }

        return ReportItem::collection($query->paginate());
    }

    public function store(Request $request, $id)
    {
        $report = new Report();

        $report->reason = $request->get("reason");
        $report->user_id = auth("api")->id();

        if ($request->is("api/videos/{$id}/report")){
            $model = Video::findOrFail($id);
            $report->reported_user_id = $model->user_id;
        }

        if ($request->is("api/channels/{$id}/report")){
            $model = Channel::findOrFail($id);
            $report->reported_user_id = $model->user_id;
        }

        if ($request->is("api/comments/{$id}/report")){
            $model = Comment::findOrFail($id);
            $report->reported_user_id = $model->user_id;
        }

        $model->reports()->save($report);

        return ReportItem::make($report);
    }
}
