<?php

namespace App\Observers;

use DOMDocument;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Models\Review;

class CustomCrawlerObserver extends CrawlObserver {

    private $content = [];

    public function __construct() {
        $this->content = NULL;
    }  
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     */
    public function willCrawl(UriInterface $url)
    {
        Log::info('willCrawl',['url'=>$url]);
    }

    /**
     * Called when the crawler has crawled the given url successfully.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    )
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($response->getBody());

        $xpath = new \DOMXpath($dom);

        $result = [];

        $reviews = $xpath->query('//div[contains(@class, "one_reviews_ins")]');
        foreach ($reviews  as $row) {

          $author =  $row->getElementsByTagName('span')[0]->textContent;
          $date = $xpath->query('//div[contains(@class, "one_reviews_date")]', $row)->item(0)->textContent;
          $text = $row->getElementsByTagName('p')[0]->textContent;
          $this->content[] = [ 'name' => $author, 'date' => $date, 'text' => $text ];

       }
       //dd ($this->content);

    }
     /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \GuzzleHttp\Exception\RequestException $requestException
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    )
    {
        Log::error('crawlFailed',['url'=>$url,'error'=>$requestException->getMessage()]);
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling()
    {
        Log::info("finishedCrawling");
        //# store in DB
        $service = app()->make('App\Services\ReviewService');
        app()->call([$service, 'store'], ['content' => $this->content]);
    }
}
