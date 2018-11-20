<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/',function(){
	phpinfo();
});
Route::get('zzz','SpiderController@zzz');
//Route::get('hh/comic','HHSpiderController@title');
//Route::get('/','HHSpiderController@title');
Route::get('hh/comic','HHSpiderController@title');
Route::get('hh/comicpage','HHSpiderController@comicpage');
Route::get('hh/chapter','HHSpiderController@chapter');
Route::get('hh/img','HHSpiderController@getimgurl');
Route::get('unsuan','HHSpiderController@unsuan');
//Route::post('hh/savedata','HHSpiderController@savedata');
//Route::get('hh/pushcomic','HhComicDataController@pushcomicname');
//Route::get('hh/pushchapter','HhComicDataController@pushchapter');
Route::get('hhdata/pushcomic','HhComicDataController@pushcomic');
Route::get('hhdata/pushchapter','HhComicDataController@pushchapter');
Route::get('hhdata/changechapter','HhComicDataController@changechapter');
Route::get('hhdata/updatecomic','HhComicDataController@updatecomic');
Route::get('hhdata/bindchapter','HhComicDataController@bindchapter');
Route::get('hhdata/setzero','HhComicDataController@setzero');
Route::get('N/title','NhentaiSpiderController@title');