<?php

namespace App\Console\Commands;
use App\Comic;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use App\ComicChapter;
use App\ComicImg;
use App\TempComic;
use App\TempComicDetail;
use App\TempComicImg;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class EHentaiImg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'EHentai:img';
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 10;  // 同时并发抓取


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ehentai 标题抓取';

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
        set_time_limit(0);
        ini_set('memory_limit', '1280M');
        $comic = Comic::where('id','>','13928')->select('id','href')->get();
        $count = $comic->count();
        $this->url = array_chunk($comic->toArray(),10);
        $this->totalPageCount = $count;
        $client = new Client();

        $requests = function ($total) use ($client) {
            foreach ($this->url as $this->key=>$item) {
                foreach ($item as $this->key1=>$uri){
                    yield function() use ($client, $uri) {
                        $this->uri =  $uri ;
                        return $client->getAsync($uri['href']);
                    };
                }
                sleep(1);
                echo '休息1秒'.PHP_EOL;
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo $this->key.'-'.$this->key1.PHP_EOL;
                echo '爬取'.$this->uri['href'].PHP_EOL;
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                if ($response->getStatusCode() ==200){
                    $arr = $crawler->filter('#thumbnail-container > div')->each(function ($node,$i) use ($http) {
                        $smallimg = $node->filter('div > a > img')->attr('data-src');
                        $pre = str_replace("https://t","https://i",$smallimg);
                        $suffix = str_after($pre,'t.');
                        $data['comic_img_url'] = str_replace("t.".$suffix,".".$suffix,$pre);
                        $data['number'] = str_before(str_after(str_after($data['comic_img_url'],'https://i.nhentai.net/galleries/'),'/'),'.');
                        $data['href'] = $smallimg;
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        $data['comic_id'] =  $this->uri['id'];
                        return $data;
                    });
                    $bool = DB::table('chapter_img')->insert($arr);
                    if ($bool){
                        echo 'success'.PHP_EOL;
                    }else{
                        echo 'fail'.PHP_EOL;
                    }
                }else{
                    $filepath = public_path('fail.txt');
                    $filebool =  file_exists($filepath);
                    if ($filebool==false){
                        mkdir(public_path('fail.txt'));
                    }
                    $handle=fopen($filepath,"a+");
                    fwrite($handle,$this->uri."\n");
                    fclose($handle);
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


    public function chapterimg()
    {
         Comic::where('id','>','13928')->get();
    }
    

    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            return;
        }
    }

}
