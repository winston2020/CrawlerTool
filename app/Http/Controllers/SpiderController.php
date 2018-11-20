<?php

namespace App\Http\Controllers;

use App\Title;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class SpiderController extends Controller
{
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 300;  // 同时并发抓取

    protected $startUrl = 'https://www.autotimes.com.cn/news/1.html';




    public function title()
    {
        set_time_limit(0);
        ini_set('memory_limit', '128M');
        for ($i=1;$i<150;$i++){
            $this->url[] = 'http://www.zhainanfulishe.com/tag/acg/page/'.$i;
        }
        $this->totalPageCount = 1500;
        $client = new Client();
        $requests = function ($total) use ($client) {
            foreach ($this->url as $uri) {
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->url[$index];
                echo '<br>';
                ob_flush();
                flush();
//                    $http = iconv('GB2312', 'UTF-8', $response->getBody()->getContents());
                    $http = $response->getBody()->getContents();

                    $crawler = new Crawler();
                    $crawler->addHtmlContent($http);

                    $arr = $crawler->filter('#main-wrap-left > div.bloglist-container.clr > article')->each(function ($node,$i) use ($http) {
                            $data['href'] = $node->filter('article > div.home-blog-entry-text.clr > h3 > a')->attr('href');
                            $data['title'] = $node->filter('article > div.home-blog-entry-text.clr > h3 > a')->text();
                            $data['author'] = $node->filter('article > div.home-blog-entry-text.clr > div > span.postlist-meta-cat > a')->text();
                            $data['cover'] = $node->filter('article > a > div > img')->attr('src');
                            $data['created_at'] = date('Y-m-d H:i:s');
                            $data['updated_at'] = date('Y-m-d H:i:s');
                            return $data;
                    });
                    $bool = DB::table('title')->insert($arr);
                    echo $bool;
                    echo '<br>';
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

    public function content()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3280M');
        $this->title = Title::select('href','id')->get();
        foreach ($this->title as  $key=>$item){
            $html = Browsershot::url($item->href)
                ->mobile()
                ->touch()
                ->bodyHtml();
            dd($html);
        }

//        $this->totalPageCount = count($this->title);
//        $client = new Client();
//        $requests = function ($total) use ($client) {
//            foreach ($this->title as $uri) {
//                yield function() use ($client, $uri) {
//                    return $client->getAsync($uri->href);
//                };
//            }
//        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->title[$index]->href;
                echo '<br>';
                ob_flush();
                flush();
                try{
                    $http = $response->getBody()->getContents();
                } catch(\Exception $e) { // I guess its InvalidArgumentException in this case
                    $this->countedAndCheckEnded();
                }
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                    $data['content'] = $crawler->filter('#main-wrap-left > div.content > div.single-text')->text();
               
                    $data['titleid'] = $this->title[$index]->id;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $data['updated_at'] = date('Y-m-d H:i:s');

                $bool = DB::table('content')->insert($data);
                if ($bool){
                    echo 'save success';
                    echo '<br>';
                }else{
                    echo 'save fail';
                    echo '<br>';
                }
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

    public function zzz()
    {
        $str = '[{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/000.jpg\", caption: \"[000]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/001.jpg\", caption: \"[001]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/002.jpg\", caption: \"[002]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/003.jpg\", caption: \"[003]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/004.jpg\", caption: \"[004]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/005.jpg\", caption: \"[005]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/006.jpg\", caption: \"[006]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/007.jpg\", caption: \"[007]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/008.jpg\", caption: \"[008]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/009.jpg\", caption: \"[009]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/010.jpg\", caption: \"[010]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/011.jpg\", caption: \"[011]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/012.jpg\", caption: \"[012]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/013.jpg\", caption: \"[013]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/014.jpg\", caption: \"[014]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/015.jpg\", caption: \"[015]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/016.jpg\", caption: \"[016]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/017.jpg\", caption: \"[017]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/018.jpg\", caption: \"[018]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/20.jpg\", caption: \"[20]\"},{ url: fast_img_host+\"//img1.wnacg.download/data/0625/39/21.jpg\", caption: \"[21]\"},{ url: fast_img_host+\"/themes/weitu/images/bg/shoucang.jpg\", caption: \"喜歡紳士漫畫的同學請加入收藏哦！\"}]';




        $data = str_after($str,'fast_img_host+\"');
        $data = str_before($data,'\"');
        dd($data);

        $crawler = new Crawler();
        $crawler->addHtmlContent($a);
        $arr = $crawler->filter('#img_list > div')->each(function ($node,$i) use ($a) {
            $data['href'] = $node->filter('div > img')->attr('src');
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $data;
        });
        dd($arr);
    }
}