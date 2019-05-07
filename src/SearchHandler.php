<?php

namespace ScoreSaberApi;

use Goutte\Client;

class SearchHandler extends BaseHandler {

    public function makeSearch( $params )
    {
        if(!empty($params['page']))
            $params['url'] = $params['url'].'?page='.$params['page'];
        $client = new Client();
        $crawler = $client->request('GET', $params['url_import']);
        $crawler = $client->request('GET', $params['url']);
        return $crawler;
    }

    /**
	 * @param \SimpleXMLElement $doc
	 * @return \Generator|Song[]
	 */
	public function getSongsFromDoc($params) {
        $html = '';
        $songs = [];
        $browser = $this->makeSearch($params);
        foreach($browser as $key => $domElement) {

            $html.= $domElement->ownerDocument->saveHTML();

        }
        $doc = $this->getDoc($html);
        $rows = $doc->xpath("//table[contains(@class, 'ranking songs')]/tbody/tr");
		if (!$rows) {
			return;
		}
		foreach ($rows as $row) {
            $image = 'https://beatsaver.com'.(string)$row->xpath(".//img[contains(@src, 'imports/images')]")[0]->attributes()->src;
			$id = (int)substr((string)$row->xpath(".//a[contains(@href, '/leaderboard/')]")[0]->attributes()->href, 13);
            $title = (string)$row->xpath(".//a[contains(@href, '/leaderboard/')]")[0];
            $mapper = (string)$row->xpath(".//a[contains(@href, '?search')]")[0];
            $linkMapper = 'https://scoresaber.com/'.(string)$row->xpath(".//a[contains(@href, '?search')]")[0]->attributes()->href;
            $linkDownload = $this->getSongDownload($title, $mapper);
            $difficulty = (string)$row->xpath("./td[contains(@class, 'difficulty')]/span")[0];
            $songs[] = new Song($id, $title, $difficulty, $image, $mapper, $linkMapper, $linkDownload);
        }
        return $songs;
    }

    /**
     * Get song download link from Beat Saver
     * @param title
     * @param mapper
     * @return string $downloadlink
     */
    public static function getSongDownload($title, $mapper) {

        $html = '';
        $params = array('key' => trim(strtolower($title)).' '.trim(strtolower($mapper)));
        $queryString =  http_build_query($params);
        $client = new Client();
        $crawler = $client->request('GET', 'https://beatsaver.com/search/all/?'.$queryString);
        foreach($crawler as $key => $domElement) {

            $html.= $domElement->ownerDocument->saveHTML();

        }
        $doc = SearchHandler::getDoc($html);
        $downloadlink = !empty($doc->xpath(".//a[contains(@href, 'https://beatsaver.com/browse/detail/')]")) ? (string)$doc->xpath(".//a[contains(@href, 'https://beatsaver.com/browse/detail/')]")[0]->attributes()->href : '';
        return $downloadlink;

    }
    
    public static function getDoc($html) {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $path = simplexml_import_dom($doc);
        return $path;
    }

    public function getLastRankedSongs() {
        
        $params = array(
            'url_import' => 'https://scoresaber.com/imports/user-setting.php?verified=0&ranked=1&sort=desc&cat=1&star=20&star1=0',
            'page' => 1,
            'url' => 'https://scoresaber.com'
        );
        $songs = $this->getSongsFromDoc($params);
        return $songs;

    }

}