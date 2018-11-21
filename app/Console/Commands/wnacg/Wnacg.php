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

class Wnacg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wnacg:comic';
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
        for ($i=0; $i <env('SERIES_PAGE') ; $i++) {
            $this->url[] = "https://wnacg.com/albums-index-page-".$i."-cate-".env('TYPE').".html";
        }

        $this->totalPageCount = 2989;
        $client = new Client();
        $requests = function ($total) use ($client) {
            foreach ($this->url as $uri) {
                yield function() use ($client, $uri) {
                    $this->uri = $uri;
                    return $client->getAsync($uri);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->uri.PHP_EOL;
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                if ($response->getStatusCode() ==200){
                    $arr = $crawler->filter('#bodywrap > div.grid > div.gallary_wrap > ul > li')->each(function ($node,$i) use ($http) {
                        $data['href'] = 'https://wnacg.com'.$node->filter('li > div.pic_box > a')->attr('href');
                        $data['name'] = $node->filter('li > div.info > div.title > a')->text();
                        $data['comic_img_url'] ='https:'.$node->filter('li > div.pic_box > a > img')->attr('src');
                        $data['description'] = '';
                        $data['star_number'] = 5;
                        $data['weekupdate'] = '完结';
                        $data['click_number'] = rand(10000,99999);
                        $data['buzz'] = rand(10000,99999);
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        $data['userid'] = 4;
                        $data['series_id'] = env('SERIES_ID');
                        $data['mark'] = env('MARK');
                        return $data;   
                    }); 
                    $bool = DB::table('comic')->insert($arr);
                    if ($bool){
                        echo 'success'.PHP_EOL;
                    }else{
                        echo 'fail'.PHP_EOL;
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
