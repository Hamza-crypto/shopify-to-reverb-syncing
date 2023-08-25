<?php

namespace App\Http\Controllers;

use Campo\UserAgent;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class ScrappingController extends Controller
{
    public function playstore($publisherId)
    {
        $base_url = "https://play.google.com";
        $url = sprintf('%s/store/apps/developer?id=%s', $base_url, $publisherId);

        $client = new Client(HttpClient::create(array(
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'no-cache',
            ),
        )));
        $client->setServerParameter('HTTP_USER_AGENT', UserAgent::random());

        $crawler = $client->request('GET', $url);
        $data = [];
        try {
            $crawler->filter('.ULeU3b')->each(function (Crawler $node) use (&$data, $client, $base_url) {
                $tagLink = $node->filter('.Si6A0c.Gy4nib')->attr('href');
                $linkedPageUrl = $base_url . $tagLink;
                $data[] = $this->parse_detail_page($linkedPageUrl, $client);
            });

            // header('Content-Type: application/json');
            echo json_encode($data);

        } catch (\Exception $e) {
            dd("Exception: " . $e->getMessage());
        }

    }

    public function parse_detail_page($url, $client)
    {
        $detailPageHtml = $client->request('GET', $url)->html();
        $crawler = new Crawler($detailPageHtml);

        $data = [
            'title' => null,
            'developer' => null,
            'rating' => null,
            'reviews' => null,
            'description' => null,
            'downloads' => null,
            'logo' => null,
            'screenshots' => [],
            'updated_on' => null,
            'url' => $url,
        ];

        try {
            $data['title'] = $crawler->filter('h1[itemprop="name"] > span')->text();
        } catch (\Exception $e) {}

        try {
            $data['developer'] = $crawler->filter('.Vbfug a span')->text();
        } catch (\Exception $e) {}

        try {
            $data['rating'] = $crawler->filter('[itemprop="starRating"]')->text();
        } catch (\Exception $e) {}

        try {
            $data['reviews'] = $crawler->filter('.g1rdde')->eq(0)->text();
        } catch (\Exception $e) {}

        try {
            $data['description'] = $crawler->filter('.bARER')->text();
        } catch (\Exception $e) {}

        try {
            $data['downloads'] = $crawler->filter('.ClM7O')->eq(1)->text();
        } catch (\Exception $e) {}

        try {
            $data['logo'] = $crawler->filter('.Mqg6jb.Mhrnjf img')->eq(0)->attr('src');
        } catch (\Exception $e) {}

        try {
            $data['updated_on'] = $crawler->filter('.xg1aie')->text();
        } catch (\Exception $e) {}

        $crawler->filter('.ULeU3b.Utde2e img')->each(function (Crawler $node) use (&$data) {
            try {
                $data['screenshots'][] = $node->attr('src');
            } catch (\Exception $e) {}
        });

        return $data;
    }
}
