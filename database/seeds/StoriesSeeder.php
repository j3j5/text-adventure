<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         DB::table('stories')->insert([
            'owner' => "0003Julio",
            'file' => storage_path("app/aquienvoto.json"),
            'json' => file_get_contents(storage_path("app/aquienvoto.json")),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
