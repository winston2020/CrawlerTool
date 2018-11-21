<?php

namespace App\Console\Commands\wnacg;

use Illuminate\Console\Command;
use App\Comic;
use Illuminate\Support\Facades\DB;

class WnacgChapter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wnacg:chapter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取wnacg章节';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', '1280M');
        $comic =  Comic::where(['series_id'=>env('SERIES_ID'),'mark'=>env('MARK')])->get();
        foreach ($comic as $key => $value) {
            # code...
             $data['number'] =1;
             $data['chapter'] = $value->name;
             $data['comic_id'] = $value->id;
             $data['href'] = $value->href;
             $data['created_at'] = date('Y-m-d H:i:s');
             $data['updated_at'] = date('Y-m-d H:i:s');
             $data['mark'] = env('MARK');
             $bool = DB::table('comic_chapter')->insert($data);
             $this->info('id=>'.$key.' status=>'.$bool.PHP_EOL);
        }
    }
}
