<?php

namespace App\Console\Commands\wnacg;

use Illuminate\Console\Command;
use App\Comic;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use App\ComicChapter;
use App\ComicImg;
use App\TempComic;
use App\TempComicDetail;
use App\TempComicImg;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class WnacgUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wnacg:update';
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 200;  // 同时并发抓取
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取wnacg漫画数据';

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
        ini_set('memory_limit', '128M');
        $comic =  Comic::where(['series_id'=>env('SERIES_ID')])->get();

        foreach ($comic as $key=>$item){
            $res = Comic::find($item->id);
            $res->mark = env('MARK');
            $bool = $res->save();
            if ($bool){
                echo 'id=>'.$res->id.' success'.PHP_EOL;
            }
        }
    }

     public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            return;
        }
    }
}
