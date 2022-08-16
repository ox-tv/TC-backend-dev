<?php

namespace App\Http\Controllers;

use App\Events\Comments\CommentCreated;
use App\Http\Requests\CommentReply;
use App\Http\Resources\Comment\CommentResource;
use App\Models\Comment;
use App\Models\CommentUser;
use App\Models\Option;
use App\Models\Scopes\OrderDescScope;
use App\Repository\Eloquent\CommentRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    private $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function index(Request $request)
    {
        $query = Comment::onlyParent();

        if($request->is('api/publisher/comments')){
            $query->whereHas('video', function (Builder $query) {
                $query->where('user_id', auth('api')->id());
            });
        }else{
            $query->hasVideo();
        }

        $perPage = $request->get('per_page')? min($request->get('per_page'), 200) : 15;
        $filters = $request->get('filters', []);
        $videosFilter = Arr::get($filters, 'videos');
        $timeFilter = Arr::get($filters, 'time');
        $justRemembersFilter = Arr::get($filters, 'just_remembers');
        $justMyMentionsFilter = Arr::get($filters, 'just_my_mentions');
        $justUnreadMentionsFilter = Arr::get($filters, 'just_unread_replies');

        if($justRemembersFilter){
            $query->whereHas('rememberedBy');
        }

        if($justMyMentionsFilter){
            /*$query->whereHas('mentions', function (Builder $query) {
                $query->where('id', auth('api')->id());
            });*/
            $query->whereHas('replies', function (Builder $query) {
                $query->whereHas('mentions', function (Builder $query) {
                    $query->where('id', auth('api')->id());
                });
            });
        }

        if($justUnreadMentionsFilter){
            $query->whereHas('replies', function ($q){
                $q->whereNull('read_at');
            });
        }

        if($videosFilter){
            $videoIds = explode(',', $videosFilter);
            $query->inVideos($videoIds);
        }

        switch ($timeFilter){
            case 'last_hour':{
                $query->lastHour();
                break;
            }
            case 'last_day':{
                $query->lastDay();
                break;
            }
            case 'last_week':{
                $query->lastweek();
                break;
            }
            case 'last_month':{
                $query->lastMonth();
                break;
            }
            case 'last_season':{
                $query->lastSeason();
                break;
            }
            default:{

            }
        }

        $sort = $request->get('sort');
        if (!empty($videoIds)){
            $query->orderBy('is_pinned','Desc');
        }
        if($sort === 'most_liked'){
            $query->withCount(['likedBy', 'dislikedBy'])->orderByRaw('(liked_by_count - disliked_by_count) DESC');
        }
        if($sort === 'oldest'){
            $query->withoutGlobalScope(OrderDescScope::class)->orderBy('created_at');
        }
        if($sort === 'newest_mentions'){
            $query->withoutGlobalScope(OrderDescScope::class)
                ->orderBy('last_mentioned_at', 'desc');
        }

        $comments = $query->paginate($perPage);

        $comments->load([
            'video',
            'user.channel',
        ])->append([
            'is_liked',
            'is_disliked',
            'is_remembered',
            'likes_count',
            'dislikes_count',
            'replies_count',
        ]);

        if($request->is('api/publisher/comments')){
            $comments->append(['is_read_replies', 'last_mentioned_at']);
        }

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    public function show(Request $request, Comment $comment)
    {
        $comment->load([
            'video',
            'replies.user',
            'user.channel',
        ])->append([
            'is_liked',
            'is_disliked',
            'likes_count',
            'dislikes_count',
            'replies_count',
        ]);

        $comment->replies->append([
            'is_liked',
            'is_disliked',
            'likes_count',
            'dislikes_count',
        ]);

        $comment->replies->each(function ($item, $key) {
            $item->user->append('is_publisher');
        });

        $comment->replies->load('mentions');

        if($request->is('api/publisher/*')){
            Comment::where('parent_id', $comment->id)
                ->update(['read_at' => Carbon::now()]);
        }

        return CommentResource::make($comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $isAdmin = $request->is('api/admin/*');
        $comment = Comment::whereId($id)->firstOrFail();

        if (!$isAdmin && auth('api')->id() != $comment->user_id){
            return response()->json(["message" => "You do not have access to delete this comment"],403);
        }

        $reasonKey = null;
        $reasonText = null;

        if ($isAdmin){
            $request->validate([
                'reason' => 'required'
            ]);

            $option_key = Option::COMMENT_DELETE_REASONS;
            $reasons = json_decode(Option::where("key", $option_key)->first()->value) ?? abort(404);

            if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
                $reasonKey = $request->get('reason');
                $reasonText = $reasons[$key]->value;
            }else{
                $reasonKey = 'other';
                $reasonText = $request->get('reason');
            }
        }

        $this->commentRepository->destroy($id, ['reason_key' => $reasonKey, 'reason_text' => $reasonText]);

        return response()->json(["message" => "ok"]);
    }


    /**
     * Reply to a comment
     * @param CommentReply $request
     * @param Comment $comment
     */
    public function reply(CommentReply $request, Comment $comment)
    {
        $reply = new Comment();
        $reply->text = $request->get('text');
        $reply->user_id = Auth::user()->id;
        $reply->video_id = $comment->video_id;
        $reply->parent_id = $comment->id;
        $reply->save();

        //$comment->parent()->save($reply);

        if (!empty($request->get('mentions'))){
            foreach ($request->get('mentions') as $id){
                $mentions[$id] = ['relation' => CommentUser::MENTION_RELATION];
            }
            $reply->mentions()->attach($mentions);

            // update last_mentioned_at on parent row
            $comment->last_mentioned_at = Carbon::now();
            $comment->save();
        }

        event(new CommentCreated($reply));

        $reply->load([
            'video',
            'replies.user',
            'user.channel',
        ])->append([
            'is_liked',
            'is_disliked',
            'likes_count',
            'dislikes_count',
            'replies_count',
        ]);

        return CommentResource::make($reply);
    }

    public function pin(Comment $comment)
    {
        Comment::where('video_id', $comment->video_id)->onlyParent()->update([
            'is_pinned' => false,
            'pinned_by' => null,
        ]);

        $comment->is_pinned = true;
        $comment->pinned_by = auth()->id();
        $comment->save();

        return CommentResource::make($comment);
    }

    public function unpin(Comment $comment){
        $comment->is_pinned = false;
        $comment->pinned_by = null;
        $comment->save();

        return CommentResource::make($comment);
    }

    public function markAllAsReadReplies()
    {
        $user = auth('api')->user();

        Comment::whereHas('video', function ($q){
            $q->where('user_id', auth('api')->id());
        })->update(['read_at' => Carbon::now()]);

        return response()->json(['status' => 'ok']);
    }

    public function stats()
    {
        $result = [
            'remembers' => 0,
            'unread_mentions' => 0,
        ];

        $result['remembers'] = CommentUser::where('user_id', auth('api')->id())->where('relation', CommentUser::REMEMBERED_RELATION)->count();

        $result['unread_mentions'] = Comment::whereHas('video', function (Builder $query) {
                $query->where('user_id', auth('api')->id());
            })->whereHas('replies', function ($q){
                $q->whereNull('read_at');
            })->onlyParent()->count();

        return $result;
    }
}
