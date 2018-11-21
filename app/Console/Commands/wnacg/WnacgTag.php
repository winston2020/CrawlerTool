<?php

namespace App\Console\Commands\Wnacg;

use App\ComicTag;
use App\Tag;
use Illuminate\Console\Command;
use App\ComicChapter;
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

class WnacgTag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wnacg:tag';
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 200;  // 同时并发抓取

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
                    return $client->getAsync($uri->href);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->url[$index]->href.PHP_EOL;
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                if ($response->getStatusCode() ==200){
                    //tag
                    $arr = $crawler->filter('#bodywrap > div > div.asTBcell.uwconn > div > a')->each(function ($node,$i) use ($http) {
                        $data['tag'] = $node->filter('a')->text();
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        return $data;
                    });
                    array_pop($arr);
                    if (!empty($arr)){
                        foreach ($arr as $item){
                            $res = Tag::where(['name'=>$item['tag']])->first();
                            if (empty($res)){
                                $tag = new Tag();
                                $tag->name = $item['tag'];
                                $bool = $tag->save();
                                if ($bool){
                                    $this->info('tag=>'.$item['tag'].'  success');
                                    $comictag = new ComicTag();
                                    $comictag->tag_id = $tag->id;
                                    $comictag->comic_id = $this->url[$index]->comic_id;
                                    $comictag->save();
                                    $this->info('id=>'.$this->url[$index]->comic_id.'tag 关联成功'.PHP_EOL);
                                }else{
                                    $this->info('tag=>'.$item['tag'].'  fail');
                                }
                            }else{
                                $this->info('同名tag已经存储 直接使用'.PHP_EOL);
                                $comictag = new ComicTag();
                                $comictag->tag_id = $res->id;
                                $comictag->comic_id = $this->url[$index]->comic_id;
                                $comictag->save();
                                $this->info('id=>'.$this->url[$index]->comic_id.'tag 关联成功'.PHP_EOL);
                            }
                        }
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
