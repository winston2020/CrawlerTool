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

class EHentai extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'EHentai:title';
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
        ini_set('memory_limit', '128M');
        for ($i=610;$i<1185;$i++){
            $url[] = 'https://nhentai.net/search/?q=chinese&page='.$i;
        }
        $this->url = array_chunk($url,10);
        $this->totalPageCount = 1185;
        $client = new Client();
        $res = $client->get('http://47.96.139.87:8081/Index-generate_api_url.html?packid=7&fa=5&qty=100&port=1&format=json&ss=5&css=&ipport=1&pro=&city=');
        $id = json_decode($res->getBody()->getContents(),true);
        for ($i=0;$i<count($id['data']);$i++){
            $this->prox[] = $id['data'][$i]['IP'];
        }
        $requests = function ($total) use ($client) {
            foreach ($this->url as $this->key=>$item) {
                foreach ($item as $this->key1=>$uri){
                    yield function() use ($client, $uri) {
                        $this->uri =  $uri ;
                        return $client->getAsync($uri);
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
                echo '爬取'.$this->uri.PHP_EOL;
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                if ($response->getStatusCode() ==200){
                    $arr = $crawler->filter('#content > div.container.index-container > div')->each(function ($node,$i) use ($http) {
                        $data['href'] = 'https://nhentai.net'.$node->filter('div > a')->attr('href');
                        $data['name'] = $node->filter('div > a > div')->text();
                        $data['comic_img_url'] = $node->filter('div > a > img')->attr('data-src');
                        $data['description'] = '';
                        $data['star_number'] = 5;
                        $data['weekupdate'] = '完结';
                        $data['click_number'] = rand(10000,99999);
                        $data['buzz'] = rand(10000,99999);
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        $data['userid'] = 4;
                        $data['series_id'] = 4;
                        return $data;
                    });
                    $bool = DB::table('comic')->insert($arr);
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



    

    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            return;
        }
    }

}
