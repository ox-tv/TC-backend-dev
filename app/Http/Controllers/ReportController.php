<?php

namespace App\Http\Controllers;

use App\Http\Resources\Comment\CommentItem;
use App\Http\Resources\Report\ReportItem;
use App\Http\Resources\Report\ReportMinimalItem;
use App\Http\Resources\Video\VideoMinimalItem;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Option;
use App\Models\Report;
use App\Models\User;
use App\Models\Video;
use App\Notifications\ReportComment;
use App\Notifications\ReportVideo;
use App\Notifications\TCNotification\TCNotification;
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
            $query->whereIn("reason", (array) $reasonFilter);
        }

        return ReportMinimalItem::collection($query->paginate());
    }

    public function store(Request $request, $id)
    {
        $report = new Report();

        //$report->reason = $request->get("reason");
        $report->user_id = auth('api')->id();



        if ($request->is("api/videos/{$id}/report")){
            $option_key = 'report_video_reasons';
            $model = Video::findOrFail($id);
            $report->reported_user_id = $model->user_id;
            $model_name = 'video';
        }

        if ($request->is("api/comments/{$id}/report")){
            $option_key = 'report_comment_reasons';
            $model = Comment::findOrFail($id);
            $report->reported_user_id = $model->user_id;
            $model_name = 'comment';
        }

        $reasons = json_decode(Option::where("key", $option_key)->first()->value, true) ?? abort(404);

        if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
            $report->reason_key = $request->get('reason');
            $report->reason_text = $reasons[$key]->value;
        }else{
            $report->reason_key = 'other';
            $report->reason_text = $request->get('reason');
        }

        if($model->reports()->where('user_id', auth('api')->id())->exists()){
            return response()->json(['message' => 'reports.already_submitted'],400);
        }

        $model->reports()->save($report);


        $notification = Notification::where([
            'entity_type' => get_class($model),
            'entity_id' => $model->id,
            'type' => 'ReportVideo',
            'scope' => Notification::SCOPE_ADMIN,
        ])->first();

        if ($notification){
            $payload = [
                $model_name => VideoMinimalItem::make($model),
                'report' => $report,
                'report_count' => $notification->payload['report_count'] + 1
            ];
            $notification->payload = $payload;
            $notification->save();
        }else{
            $admins = User::admins()->get();
            $payload = [
                $model_name => VideoMinimalItem::make($model),
                'report' => $report,
                'report_count' => 1
            ];

            if($model_name == 'video'){
                TCNotification::send($admins, new ReportVideo(
                    Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
                    Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                    $payload,
                    get_class($model),
                    $model->id
                ));
            }else{
                TCNotification::send($admins, new ReportComment(
                    Notification::SCOPE_TEXT[Notification::SCOPE_ADMIN],
                    Notification::USER_GROUP_TEXT[Notification::USER_GROUP_CUSTOM],
                    $payload,
                    get_class($model),
                    $model->id
                ));
            }
        }

        return ReportItem::make($report);
    }
}
