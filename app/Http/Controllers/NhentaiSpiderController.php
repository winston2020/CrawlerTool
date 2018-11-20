<?php

namespace App\Http\Controllers;

use App\Comic;
use App\ComicChapter;
use App\ComicImg;
use App\TempComic;
use App\TempComicDetail;
use App\TempComicImg;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class NhentaiSpiderController extends Controller
{
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 300;  // 同时并发抓取


    public function title()
    {
        set_time_limit(0);
        ini_set('memory_limit', '128M');
        for ($i=0;$i<1185;$i++){
            $url[] = 'https://nhentai.net/search/?q=chinese&page='.$i;
        }
        $this->url = array_chunk($url,9);
        $this->totalPageCount = 1185;
        $client = new Client();
        $res = $client->get('http://47.96.139.87:8081/Index-generate_api_url.html?packid=7&fa=5&qty=100&port=1&format=json&ss=5&css=&ipport=1&pro=&city=');
        $id = json_decode($res->getBody()->getContents(),true);
        for ($i=0;$i<count($id['data']);$i++){
            $this->prox[] = $id['data'][$i]['IP'];
        }
        $requests = function ($total) use ($client) {
            foreach ($this->url as $item) {
                foreach ($item as $uri){
                    yield function() use ($client, $uri) {
                        return $client->getAsync($uri);
                    };
                }
                sleep(2);
                echo '休息2秒'.PHP_EOL;
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->url[$index];
                echo '<br>';
                ob_flush();
                flush();
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
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
                    echo 'success';
                    echo '<br>';
                }else{
                    echo 'fail';
                    echo '<br>';
                }
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

    public function findNum($str=''){
        $str=trim($str);
        if(empty($str)){return '';}
        $temp=array('1','2','3','4','5','6','7','8','9','0');
        $result='';
        for($i=0;$i<strlen($str);$i++){
            if(in_array($str[$i],$temp)){
                $result.=$str[$i];
            }
        }
        return $result;
    }

    public function chapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3280M');
//        $this->data = Comic::where(['series_id'=>3,'author'=>''])->select('href','id','name')->get();
        $comic = Comic::where(['series_id'=>2])->get();
        foreach ($comic as $item){
           $chapter = ComicChapter::where(['comic_id'=>$item->id])->get();
           if ($chapter->isEmpty()){
               $comicid[] = $item->id;
           }
        }
        $this->data = Comic::whereIn('id',$comicid)->get();
        $this->totalPageCount = count($this->data);
        $client = new Client();
        $requests = function ($total) use ($client) {
            foreach ($this->data as $uri) {
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri->href);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->data[$index]->href;
                echo '<br>';
                $this->index =$index;
                ob_flush();
                flush();
                $statuscode = $response->getStatusCode();
                if (!$statuscode==200){
                    $this->countedAndCheckEnded();
                }

                try{
                    $http = $response->getBody()->getContents();
                } catch(\Exception $e) { // I guess its InvalidArgumentException in this case
                    $this->countedAndCheckEnded();
                }
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                $data['weekupdate'] =str_after($crawler->filter('#about_kit > ul > li:nth-child(3)')->text(),':') ;
                $data['description'] =str_after($crawler->filter('#about_kit > ul > li:nth-child(8)')->text(),':') ;
                $data['author'] = str_after($crawler->filter('#about_kit > ul > li:nth-child(2)')->text(),':') ;
                $data['star_number'] = 5;
                $data['click_number'] = rand(10000,99999);
                $data['series_id'] = 3;

                $comic = Comic::find($this->data[$index]->id);
                $comic->weekupdate = $data['weekupdate'];
                $comic->description = $data['description'];
                $comic->author = $data['author'];
                $comic->star_number = $data['star_number'];
                $comic->click_number = $data['click_number'];
                $bool = $comic->save();
                if ($bool){
                    echo 'comic save success';
                    echo '<br>';
                }else{
                    echo 'comic save fail';
                    echo '<br>';
                }

                $arr = $crawler->filter('#permalink > div.cVolList > ul > li')->each(function ($node,$i) use ($http) {
                    $data['href'] ='http://www.hhmmoo.com'.$node->filter('li > a')->attr('href');
                    $data['chapter'] = $node->filter('li > a')->text();
                    $number = self::findNum( $data['chapter']);
                    if (empty($number)){
                        $number = 0;
                    }
                    $data['number'] = $number;
                    $data['comic_id'] = $this->data[$this->index]->id;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    return $data;
                });
                $res =  DB::connection('mysql_main')->table('comic_chapter')->insert($arr);
                if ($res){
                    echo 'chapter save success';
                    echo '<br>';
                }else{
                    echo 'chapter save fail';
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

    public function comicpage()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2230M');
        $seriesid =2;
        echo 'find series => '.$seriesid.'data now...';
        echo '<br>';
        $comic = Comic::where(['series_id'=>$seriesid])->select('id','name')->get();
        foreach ($comic as $item){
            $comicid[] = $item->id;
            echo 'check comic《'.$item->name.'》ing...';
            echo '<br>';
            ob_flush();
            flush();
        }

        $chapterdata = ComicChapter::whereIn('comic_id',$comicid)->get();

        foreach ($chapterdata as $item){
            $res = ComicImg::where(['chapter_id'=>$item->id])->get();
            if ($res->isEmpty()){
                $chapterid[] = $item->id;
                echo 'check chapter《'.$item->chapter.'》ing...';
                echo '<br>';
                ob_flush();
                flush();
            }
        }

        echo 'get no chapterimg data  and finding...';
        echo '<br>';
        $this->data = ComicChapter::whereIn('id',$chapterid)->get();
        $this->totalPageCount = count($this->data);
        $client = new Client();
        $requests = function ($total) use ($client) {
            foreach ($this->data as $uri) {
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri->href);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->data[$index]->href;
                echo '<br>';
                ob_flush();
                flush();
                $http = $response->getBody()->getContents();
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                $number = $crawler->filter('body > div.cHeader > div.cH1 > b')->text();
                $number = (str_after($number,'/'));
                $pre = str_before($this->data[$index]->href,str_after(str_after(str_after($this->data[$index]->href,'http://'),'/'),'/'));
                $suf = str_after(str_after(str_after($this->data[$index]->href,'.'),'.'),'.');
                for ($i=1;$i<$number+1;$i++){
                    $data[$i]['href'] = $pre.$i.'.'.$suf;
                    $data[$i]['chapter_id'] = $this->data[$index]->id;
                    $data[$i]['number'] = $i;
                    $data[$i]['created_at'] = date('Y-m-d H:i:s');
                    $data[$i]['updated_at'] = date('Y-m-d H:i:s');
                }
                $bool = DB::connection('mysql_main')->table('chapter_img')->insert($data);
                if ($bool){
                    echo 'success';
                    echo '<br>';
                }else{
                    echo 'fail';
                    echo '<br>';
                }
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



    public function getimgurl()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3280M');
        $this->data = ComicImg::where('comic_img_url','=',null)->select('id','href')->get();
        $this->totalPageCount = count($this->data);
        $client = new Client();
        $requests = function ($total) use ($client) {
            foreach ($this->data as $uri) {
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri->href);
                };
            }
        };


        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){
                echo '爬取'.$this->data[$index]->href.'id:'.$this->data[$index]->id;
                echo '<br>';
                ob_flush();
                flush();
                try{
                    $http = $response->getBody()->getContents();
                } catch(\Exception $e) { // I guess its InvalidArgumentException in this case
                    $this->countedAndCheckEnded();
                }
                $statuscode = $response->getStatusCode();
                if (!$statuscode==200){
                    $failfile = fopen(public_path('fail_link.txt'),'a');
                    fwrite($failfile,$this->data[$index]->href);
                    fclose($failfile);
                }
                $crawler = new Crawler();
                $crawler->addHtmlContent($http);
                $host = $crawler->filter('#hdDomain')->attr('value');
                try
                {
                    $mark = $crawler->filter('#img1021')->attr('name');
                }
                catch(\Exception $e)
                {
                    $mark = "";
                }

                if (empty($mark)){
                    try
                    {
                        $mark = $crawler->filter('#img2391')->attr('name');
                    }
                    catch(\Exception $e)
                    {
                        $mark = "";
                    }
                }

                if (empty($mark)){
                    try
                    {
                        $mark = $crawler->filter('#img7652')->attr('name');
                    }
                    catch(\Exception $e)
                    {
                        $mark = "";
                    }
                }

                if (empty($mark)){
                    try
                    {
                        $mark = $crawler->filter('#imgCurr')->attr('name');
                    }
                    catch(\Exception $e)
                    {
                        $mark = "";
                    }
                }


                $zz = ComicImg::find($this->data[$index]->id);
                $zz->mark = $mark;
                $zz->host = $host;
                $bool = $zz->save();
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

    public function unsuan()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1180M');
        $data1 = ComicImg::where('comic_img_url','=',null)->select('id','mark','host','href')->get()->toArray();
        $data = (json_encode($data1));
        return view('unsun',compact('data'));
    }


    public function savedata(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '180M');
       $data =  $request->input('data');
       foreach ($data as $item){
           $res = ComicImg::find($item['id']);
           if (empty($item['comic_img_url'])){
               $res->comic_img_url = '';
           }else{
               $res->comic_img_url = $item['comic_img_url'];

           }
           $bool = $res->save();
       }
        echo $bool;
    }

    public function getimgurl__1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1028M');
        $url =  TempComicImg::where('id','>',92)->where('id','<',100)->get();
        foreach ($url as $item){
            $html = Browsershot::url($item->href)
                ->windowSize(480, 800)
                ->userAgent('Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Mobile Safari/537.36')
                ->mobile()
                ->touch()
                ->bodyHtml();
            $host = 'http://164.94201314.net';
            $data =  $host.str_before(str_after($html,$host),'"');
            $ComicImg = TempComicImg::find($item->id);
            $ComicImg->comic_img_url = $data;
            $bool = $ComicImg->save();
            if ($bool){
                echo 'id:'.$ComicImg->id.'success';
                echo '<br>';
            }else{
                echo 'id:'.$ComicImg->id.'fail';
                echo '<br>';
            }
            ob_flush();
            flush();
        }
    }


    public function countedAndCheckEnded()
    {
        if ($this->counter < $this->totalPageCount){
            return;
        }
    }
}