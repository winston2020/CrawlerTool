<?php

namespace App\Http\Controllers;

use App\Comic;
use App\TempComic;
use App\TempComicDetail;
use App\TempComicImg;
use Illuminate\Support\Facades\DB;

class HhComicDataController extends Controller
{
    public function pushcomic(){
        $tempcomic = TempComic::select('id','name','comic_img_url','created_at','updated_at','author','description','star_number','series_id','weekupdate','click_number','userid')->get();
        foreach ($tempcomic as $item){
            $comic = new Comic();
            $comic->name = $item->name;
            $comic->comic_img_url = $item->comic_img_url;
            $comic->created_at = $item->created_at;
            $comic->updated_at = $item->updated_at;
            $comic->author = $item->author;
            $comic->description = $item->description;
            $comic->star_number = $item->star_number;
            $comic->series_id = $item->series_id;
            $comic->weekupdate = $item->weekupdate;
            $comic->click_number = $item->click_number;
            $comic->userid = $item->userid;
            $bool = $comic->save();
            if ($bool){
                $tempcomic = TempComic::find($item->id);
                $tempcomic->newid = $comic->id;
                $res = $tempcomic->save();
                echo $res;
                echo '<br>';
                ob_flush();
                flush();
            }
        }
        $res = DB::connection('mysql_main')->table('comic')->insert($tempcomic);
        dd($res);
    }

    public function pushchapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1028M');
        $tempcomic = TempComic::all();
        foreach ($tempcomic as $item){
            $tempcomicdetail = TempComicDetail::where(['comic_id'=>$item->id])->get();
            foreach ($tempcomicdetail as $chpateritem){
                $chapterres = TempComicDetail::find($chpateritem->id);
                $chapterres->newcomicid = $item->newid;
                $savechapter = $chapterres->save();
                if ($savechapter){
                    echo '正在更新章节:《'.$chapterres->chapter.'》id=>'.$chapterres->newcomicid ;
                    echo '<br>';
                }
            }
        }
    }

    public function findlostcomic()
    {
        $data = TempComicDetail::where(['newcomicid'=>null])->select('chapter','comic_id')->get()->toArray();
        dd($data);
    }

    public function pushimg()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1028M');
    }

    public function changecomicid()
    {
        $tempcomic = TempComic::select('name','comic_img_url','created_at','updated_at','author','description','star_number','series_id','weekupdate','click_number','userid')->get()->toArray();

    }

    public static function change()
    {
        $comic =  Comic::where(['series_id'=>3,'author'=>''])->get();
        foreach ($comic as $item){
            $comicid[] = $item->id;
        }
        return $comicid;
    }

    public function changechapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1230M');
        $data = TempComicDetail::all();
        foreach ($data as $item){
            $res = TempComicImg::where('chapter_id','=',$item->id)->get();
            if ($res->isEmpty()){
                $chapterid[] = $item->id;
                echo 'chapter:'.$item->id;
                echo '<br>';
                ob_flush();
                flush();
                $res = null;
                sleep(1);
            }
        }
    }

    public function updatecomic()
    {
        $comic = TempComic::all();
        foreach ($comic as $item){
            $chapter = TempComicDetail::where('comic_id', '=',$item->id )->get();
            if (!$chapter->isEmpty()){
               echo 'id=>'.$item->id;
               echo '<br>';
                ob_flush();
                flush();
            }

//            if ($chapter==null){
//                $faildata[] = $item->id;
//            }else{
//                $data = TempComic::find($item->id);
//                $data->id = $chapter->comic_id;
//                $bool = $data->save();
//                echo $item->id.'=>'.$chapter->comic_id.':'.$bool;
//                echo '<br>';
//            }
            ob_flush();
            flush();
        }
//        $deleted = TempComic::destroy($faildata);
//        dd($deleted);
    }

    public function  bindchapter()
    {

        $comic = TempComic::all();
        foreach ($comic as $item){
                $b = TempComic::where(['id'=>$item->id])->first();
                $b->id =$b->newid;
               $bool = $b->save();
        }
    }

    public function setzero()
    {
       $str = 'http://www.dianmi.net/clu/ggby/20180927oxqa.html';
       $a = md5($str);
       dd($a);
    }

}
