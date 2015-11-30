<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Story extends Model {

    protected $table = 'stories';

    protected $fillable = ['owner', 'file', 'json',];

    public function users()
    {
        return $this->hasMany(App\User::class);
    }

}
