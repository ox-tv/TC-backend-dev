<?php

namespace App\Http\Controllers;

use App\Http\Resources\Report\ReportItem;
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

        if($userFilter){
            $query->whereIn("user_id", (array) $userFilter);
        }

        if($reportedUserFilter){
            $query->whereIn("reported_user_id", (array) $reportedUserFilter);
        }

        if($reasonFilter){
            $query->whereIn("reason", (array) $reasonFilter);
        }

        return ReportItem::collection($query->get());
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
