<?php

namespace App\Console\Commands\wnacg;

use App\ComicChapter;
use Illuminate\Console\Command;
use App\Comic;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use App\ComicImg;
use App\TempComic;
use App\TempComicDetail;
use App\TempComicImg;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;
class WnacgImg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wnacg:img';
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 200;  // 同时并发抓取
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取wnacg 图片';

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
        $this->url = ComicChapter::where(['mark'=>env('MARK')])->get();
        $this->totalPageCount = $this->url->count();
        $client = new Client();
        $requests = function ($total) use ($client) {
            foreach ($this->url as $uri) {
                yield function() use ($client, $uri) {
                    $this->comic_id = $uri->comic_id;
                    return $client->getAsync( str_replace("index","gallery",$uri->href));
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.str_replace("index","gallery",$this->url[$index]->href).PHP_EOL;
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                if ($response->getStatusCode() ==200){
                    //img
                    $after = str_after($http,'var imglist = ');
                    $str = str_before($after,'document.writeln("");');
                    $newstr =  str_replace("url: fast_img_host+\\","",$str);
                    $pieces = explode(",", $newstr);
                    foreach ($pieces as $key=>$item){
                        if($key%2){

                        }else{
                            $zef =  'https://'.str_after($item,'"//');
                            $data[] = str_before($zef,'\"');
                        }
                    }
                    array_pop($data);
                    foreach ($data as $key=>$item){
                        $newdata[$key]['comic_img_url'] = $item;
                        $newdata[$key]['comic_id'] = $this->url[$index]->comic_id;
                        $newdata[$key]['number'] = $key;
                        $newdata[$key]['chapter_id'] = $this->url[$index]->id;
                        $newdata[$key]['mark'] = env('MARK');
                        $newdata[$key]['created_at'] = date('Y-m-d H:i:s');
                        $newdata[$key]['updated_at'] = date('Y-m-d H:i:s');
                    }

                    try
                    {
                        $bool = DB::table('chapter_img')->insert($newdata);
                        if ($bool){
                            echo 'success'.PHP_EOL;
                        }else{
                            echo 'fail'.PHP_EOL;
                        }
                    }

                    catch(\Exception $e)
                    {
                        $this->line('id=>'.$this->url[$index]->id,'fail');
                    }

                }
                echo $response->getStatusCode().PHP_EOL;
                $this->countedAndCheckEnded();
            },
            'rejected' => function ($reason, $index){
//                    log('test',"rejected" );
//                    log('test',"rejected reason: " . $reason );
                $this->countedAndCheckEnded();
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

    }

    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            return;
        }
    }
}
