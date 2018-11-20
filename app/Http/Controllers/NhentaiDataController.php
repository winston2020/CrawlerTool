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

class NhentaiDataController extends Controller
{
    public function index()
    {
        $comic =  Comic::where(['series_id'=>4])->get();
        foreach ($comic as $item){
           $comicimg = ComicImg::where(['comic_id'=>$item->id])->get();
           if ($comicimg->isEmpty()){
               $noimgcomic[] = $item->id;
               echo '服务器数据为空'.PHP_EOL;
           }
        }
    }
}