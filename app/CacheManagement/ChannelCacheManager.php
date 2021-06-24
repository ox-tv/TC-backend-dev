<?php


namespace App\CacheManagement;


class ChannelCacheManager
{
    public function addToChannelsMonthLikes($channel_id, $relation, $add = true)
    {
        $channel_likes = cache('channels_month_likes');

        if(empty($channel_likes[$channel_id])){
            $channel_likes[$channel_id] = [
                'channel_id' => $channel_id,
                'likes' => 0,
                'dislikes' => 0,
                'total' => 0,
                'likes_by_day' => [],
            ];
        }


        $action = 'dislikes';

        if($relation == 1){
            $action = 'likes';
        }

        if ($add){
            $channel_likes[$channel_id][$action] += 1;
        }
        else{
            $channel_likes[$channel_id][$action] -= 1;
        }

        $channel_likes[$channel_id]['total'] = $channel_likes[$channel_id]['likes'] - $channel_likes[$channel_id]['dislikes'];


        if ($add){
            $channel_likes[$channel_id]["{$action}_by_day"][date('Y-m-d')] =
                empty($channel_likes[$channel_id]["{$action}_by_day"][date('Y-m-d')])?
                    1 : $channel_likes[$channel_id]["{$action}_by_day"][date('Y-m-d')] + 1;
        }
        else{
            $channel_likes[$channel_id]["{$action}_by_day"][date('Y-m-d')] =
                empty($channel_likes[$channel_id]["{$action}_by_day"][date('Y-m-d')])?
                    -1 : $channel_likes[$channel_id]["{$action}_by_day"][date('Y-m-d')] - 1;
        }


        $channel_likes[$channel_id]["{$action}_by_day"] = array_slice($channel_likes[$channel_id]["{$action}_by_day"], -30);

        cache()->forever('channels_month_likes', $channel_likes);

        return true;
    }

    private function plus($value1, $value2)
    {
        return $value1 + $value2;
    }

    private function minus($value1, $value2)
    {
        return $value1 - $value2;
    }
}