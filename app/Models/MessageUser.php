<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageUser extends Model
{
    use HasFactory;

    protected $table = 'message_user';
    public $timestamps = false;

    protected $primaryKey = array('user_id', 'message_id');
    public $incrementing = false;

    const STATUS_NEW = 1;
    const STATUS_SEEN = 2;
    const STATUS_CLOSE = 3;
    const STATUS_REPLIED_BY_ADMIN = 4;
    const STATUS_REPLIED_BY_USER = 5;

    const STATUS_TEXT = [
        self::STATUS_NEW => 'new',
        self::STATUS_SEEN => 'seen',
        self::STATUS_CLOSE => 'close',
        self::STATUS_REPLIED_BY_ADMIN => 'replied_by_admin',
        self::STATUS_REPLIED_BY_USER => 'replied_by_user',
    ];

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if(!is_array($keys)){
            return parent::setKeysForSaveQuery($query);
        }

        foreach($keys as $keyName){
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @param mixed $keyName
     * @return mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if(is_null($keyName)){
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }

}
