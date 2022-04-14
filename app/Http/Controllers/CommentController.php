<?php

namespace App\Http\Controllers;

use App\Events\VideoCommented;
use App\Http\Requests\CommentReply;
use App\Http\Resources\Comment\CommentResource;
use App\Models\Comment;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::query();

        if($request->is('api/publisher/comments')){
            $query->whereHas('video', function (Builder $query) {
                $query->where('user_id', auth('api')->id());
            });
        }else{
            $query->hasVideo();
        }

        $filters = $request->get('filters', []);

        $videos = Arr::get($filters, 'videos');

        if($videos){
            $videos = explode(',', $videos);
            $query->inVideos($videos);
        }

        $sort = $request->get('sort');
        if ($videos){
            $query->orderBy('is_pinned','Desc');
        }
        if($sort === 'most_liked'){
            $query->withCount(['likedBy', 'dislikedBy'])->orderByRaw('(liked_by_count - disliked_by_count) DESC');
        }

        $comments = $query->paginate();

        $comments->load([
            'video',
            'user',
        ])->append([
            'is_liked',
            'is_disliked',
            'likes_count',
            'dislikes_count',
            'replies_count',
        ]);

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

    public function show(Comment $comment)
    {
        $comment->load([
            'video',
            'replies.user',
            'user',
        ])->append([
            'is_liked',
            'is_disliked',
            'likes_count',
            'dislikes_count',
            'replies_count',
        ]);

        $comment->replies->each(function ($item, $key) {
            $item->append([
                'is_liked',
                'is_disliked',
                'likes_count',
                'dislikes_count',
            ]);

            $item->user->append('is_publisher');
        });

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
    public function destroy(Request $request, Comment $comment)
    {
        $isAdmin = $request->is('api/admin/*');

        if (!$isAdmin && auth('api')->id() != $comment->user_id){
            return response()->json(["message" => "You do not have access to delete this comment"],403);
        }

        if ($isAdmin){
            $request->validate([
                'reason' => 'required'
            ]);

            $option_key = Option::COMMENT_DELETE_REASONS;
            $reasons = json_decode(Option::where("key", $option_key)->first()->value) ?? abort(404);

            if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
                $comment->reason_key = $request->get('reason');
                $comment->reason_text = $reasons[$key]->value;
            }else{
                $comment->reason_key = 'other';
                $comment->reason_text = $request->get('reason');
            }

            $comment->save();
        }

        $comment->delete();

        return response()->json(["message" => "ok"]);
    }


    /**
     * Reply to a comment
     * @param CommentReply $request
     * @param Comment $comment
     */
    public function reply(CommentReply $request, Comment $comment){
        $reply = new Comment();
        $reply->text = $request->get('text');
        $reply->user_id = Auth::user()->id;
        $reply->video_id = $comment->video_id;
        $reply->save();

        $comment->parent()->save($reply);event(new VideoCommented($comment->video, auth('api')->user()));

        return CommentResource::make($reply);
    }

    public function pin(Comment $comment)
    {
        Comment::where('video_id', $comment->video_id)->update([
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

}
