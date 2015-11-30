<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class User extends Model {

    protected $fillable = ['username', 'last_answer', 'current_passage', 'story_id',];

    protected $primaryKey = ['username', 'story_id'];
    public $incrementing = false;

    public function story()
    {
        return $this->belongsTo(App\Story::class);
    }

    /**
     * Set the keys for a save update query.
     * This is a fix for tables with composite keys
     * TODO: Investigate this later on
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query
            //Put appropriate values for your keys here:
            ->where('username', '=', $this->username)
            ->where('story_id', '=', $this->story_id);
        return $query;
    }
}
