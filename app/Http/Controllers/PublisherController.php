<?php

namespace App\Http\Controllers;


use App\Http\Resources\ChannelSummaryCollection;
use App\Models\Channel;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function scoreBoard(){
        return new ChannelSummaryCollection(Channel::published()->paginate(50));
    }
}
