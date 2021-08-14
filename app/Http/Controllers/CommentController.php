<?php

namespace App\Http\Controllers;

use App\Events\VideoCommented;
use App\Http\Requests\CommentReply;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentItem;
use App\Models\Comment;
use App\Models\Option;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return CommentCollection
     */
    public function index(Request $request)
    {
        $query = Comment::whereNull('parent_id');

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
        if($sort === 'most_liked'){
            $query->withCount(['likedBy', 'dislikedBy'])->orderByRaw('(liked_by_count - disliked_by_count) DESC');
        }

        $comments = $query->paginate();

        return new CommentCollection($comments);
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

    /**
     * Display the specified resource.
     *
     * @param Comment $comment
     * @return CommentItem
     */
    public function show(Comment $comment)
    {
        return new CommentItem($comment);
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
        $request->validate([
            'reason' => 'required'
        ]);

        $option_key = 'comment_delete_reasons';
        $reasons = json_decode(Option::where("key", $option_key)->first()->value) ?? abort(404);

        if(($key = array_search($request->get('reason'), array_column($reasons, 'key'))) !== false ){
            $comment->reason_key = $request->get('reason');
            $comment->reason_text = $reasons[$key]->value;
        }else{
            $comment->reason_key = 'other';
            $comment->reason_text = $request->get('reason');
        }

        $comment->save();

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

        return new CommentItem($reply);
    }

    /**
     * @param Comment $comment
     * @return CommentItem
     */
    public function pin(Comment $comment){

        Comment::where('video_id', $comment->vidoe_id)->update([
            'is_pinned' => 0
        ]);

        $comment->is_pinned = 1;
        $comment->save();

        return new CommentItem($comment);
    }

    /**
     * @param Comment $comment
     * @return CommentItem
     */
    public function unpin(Comment $comment){
        $comment->is_pinned = 0;
        $comment->save();

        return new CommentItem($comment);
    }

}
