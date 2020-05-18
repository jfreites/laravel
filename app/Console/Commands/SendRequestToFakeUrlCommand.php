<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendRequestToFakeUrlCommand extends Command
{
    /**
     * Example 1: php artisan exercise:send-request single
     * 
     * Example 2: php artisan exercise:send-request multi
     *
     * @var string
     */
    protected $signature = 'exercise:send-request {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a POST request to given URL';

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
        $this->info('Hello..!');

        $action = $this->argument('action');

        $url = 'https://atomic.incfile.com/fakepost';

        if (strtoupper($action) === 'SINGLE') {

            $response = $this->sendRequest($url);

            $response = $response->body();
        }

        if (strtoupper($action) === 'MULTI') {

            $response = $this->sendMultipleRequests($url, 10);
        }

        dump($response);

        $this->info('...Bye!');
    }

    /**
     * Handle multiple requests for the same URL. This is taking care for the Question 5.
     * 
     * How to run: php artisan exercise:send-request multi
     */
    private function sendMultipleRequests($url, $total = 50)
    {
        $i = 1;

        //Initiate a multiple cURL handle
        $multiHandler = curl_multi_init();

        // Setup a bunch of requests for testing
        while ($i <= $total) {
            $i++;

            $requests[$i] = [];
            $requests[$i]['url'] = $url;

            $requests[$i]['curl_handle'] = curl_init($url);

            curl_setopt($requests[$i]['curl_handle'], CURLOPT_POST, 0);
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_POSTFIELDS, '');
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_TIMEOUT, 10);
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($requests[$i]['curl_handle'], CURLOPT_SSL_VERIFYPEER, false);

            curl_multi_add_handle($multiHandler, $requests[$i]['curl_handle']);
        }

        // watcher
        $stillRunning = false;

        do {
            // Do the magic
            curl_multi_exec($multiHandler, $stillRunning);
        } while ($stillRunning);


        // Iterate in the responses
        foreach ($requests as $key => $request) {

            curl_multi_remove_handle($multiHandler, $request['curl_handle']);
            $requests[$key]['content'] = curl_multi_getcontent($request['curl_handle']);
            $requests[$key]['http_code'] = curl_getinfo($request['curl_handle'], CURLINFO_HTTP_CODE);

            //Close the handle.
            curl_close($requests[$key]['curl_handle']);
        }

        // Close the handler
        curl_multi_close($multiHandler);

        return $requests;
    }

    /**
     * Handle a single request for the URL. This is taking care for the Question 4.
     * 
     * How to run: php artisan exercise:send-request single
     *
     */
    private function sendRequest($url)
    {
        $response = Http::post($url);

        if ($response->successful()) {
            $this->info('The request was succesful');
        }

        if ($response->clientError()) {
            $this->line('Error Status', $response->status());
            $this->error('The request was failed');
        }

        if ($response->serverError()) {
            $this->line('Error Status', $response->status());
            $this->error('The request was failed');
        }

        return $response;
    }
}
