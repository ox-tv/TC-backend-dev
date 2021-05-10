<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentReply;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\CommentItem;
use App\Models\Comment;
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
        $query = Comment::whereNull('parent_id')->hasVideo();

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
    public function destroy(Comment $comment)
    {
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

        $comment->parent()->save($reply);

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
