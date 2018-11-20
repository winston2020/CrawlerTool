<?php

namespace App\Console\Commands\Wnacg;

use Illuminate\Console\Command;

class WnacgTag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wnacg:tag';

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
                    $this->uri = $uri->href;
                    return $client->getAsync($uri->href);
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
                    //tag
                    $arr = $crawler->filter('#bodywrap > div > div.asTBcell.uwconn > div > a')->each(function ($node,$i) use ($http) {
                        $data['tag'] = $node->filter('a')->attr('text');
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data['updated_at'] = date('Y-m-d H:i:s');
                        return $data;
                    });
                    array_pop($arr);
                    dd($arr);
//                    $bool = DB::table('comic')->insert($arr);
//                    if ($bool){
//                        echo 'success'.PHP_EOL;
//                    }else{
//                        echo 'fail'.PHP_EOL;
//                    }
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
