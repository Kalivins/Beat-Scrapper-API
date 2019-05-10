<?php

namespace BeatSaberScrapperApi;

use Goutte\Client;

class SearchHandler extends BaseHandler {

    /**
     * https://scoresaber.com/imports/user-setting.php?verified=0&ranked=1&sort=desc&star=20&star1=0
     * Make a search of songs in ScoreSaber
     * Authorized parameters :
     * verified : 1 or 0 (true or false) default to 0
     * ranked : 1 or 0 (true or false) default to 1
     * category : trending or ranked or scoresset or stardifficulty or mapper - default to trending
     * sort : desc or asc - default to desc
     * maxstars = 0 - 50 - default to 20
     * minstars = 0 - 50 - default to 0
     */
    public function requestSongs($params) {
        if(!empty($params)) {
            // Define values
            $verified = !empty($params['verified']) ? (int)$params['verified'] : 0;
            $ranked = !empty($params['ranked']) ? (int)$params['ranked'] : 1;
            $category = !empty($params['category']) ? $this->getType((string)$params['category']) : $this->getType('trending');
            $sort = !empty($params['sort']) ? (string)$params['sort'] : "desc";
            $maxstars = !empty($params['maxstars']) ? (int)$params['maxstars'] : 20;
            $minstars = !empty($params['minstars']) ? (int)$params['minstars'] : 0;

            $requestUrl = "https://scoresaber.com/imports/user-setting.php?verified="
            .$verified."&ranked="
            .$ranked."&cat="
            .$category."&sort="
            .$sort."&star="
            .$maxstars."&star1="
            .$minstars;

            $parameters = array(
                'url_import' => $requestUrl,
                'page' => 1,
                'url' => 'https://scoresaber.com'
            );

            $songs = $this->getSongsFromDoc($parameters);

            return $songs;
        } else {
            return array('error' => 'songs POST route needs parameters', 'authorized params' => $this->getAuthorizedParams());
        }
    }

    public function makeSearch( $params ) {
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
            $image = 'https://scoresaber.com'.(string)$row->xpath(".//img[contains(@src, 'imports/images')]")[0]->attributes()->src;
			$id = (int)substr((string)$row->xpath(".//a[contains(@href, '/leaderboard/')]")[0]->attributes()->href, 13);
            $title = trim((string)$row->xpath(".//a[contains(@href, '/leaderboard/')]")[0]);
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
    public static function getSongDownload($title, $mapper, $try = 0) {

        $html = '';
        if($try == 1) {
            $title = explode('-', $title);
            $title = reset($title);
        }
        $key = preg_replace("/[^a-zA-Z ]/", '', trim(strtolower($title))).' '.trim(strtolower($mapper));
        $params = array('key' => $key);
        $queryString =  http_build_query($params);
        $client = new Client();
        $crawler = $client->request('GET', 'https://beatsaver.com/search/all/?'.$queryString);
        foreach($crawler as $key => $domElement) {

            $html.= $domElement->ownerDocument->saveHTML();

        }
        $doc = SearchHandler::getDoc($html);
        $downloadlink = !empty($doc->xpath(".//a[contains(@href, 'https://beatsaver.com/browse/detail/')]")) ? (string)$doc->xpath(".//a[contains(@href, 'https://beatsaver.com/browse/detail/')]")[0]->attributes()->href : '';
        if(empty($downloadlink) && $try < 1)
            return self::getSongDownload($title, $mapper, 1);
        return $downloadlink;

    }
    
    public static function getDoc($html) {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $path = simplexml_import_dom($doc);
        return $path;
    }

    public function checkLastSongs($cat) {
        if ($handle = opendir('imports/')) {

            while (false !== ($entry = readdir($handle))) {

                $fileCat = explode('.', $entry);
                $fileCat = reset($fileCat);
                if($cat == 'trending')
                    $cat = "0";
        
                if ($fileCat == $cat) {
					
                    return true;

                }
            }
        
            closedir($handle);
		}
		
		return false;
    }

    public function getLastSongsImport($cat) {
        if($cat == 'trending')
            $cat = "0";

        $userData = file_get_contents('imports/'.$id.'.json');
        return json_decode($userData, true);
    }

    public function getLastSongs($type, $checkImport = false, $page = 1) {
        
        $cat = is_int($type) ? $type : $this->getType($type);
        if(!empty($cat) && $checkImport && $this->checkLastSongs($cat)) {
            $data = $this->getLastSongsImport($cat);
            $data['import'] = true;
            return $data;
        }
        $url = 'https://scoresaber.com/imports/user-setting.php?verified=0&ranked=1&sort=desc&star=20&star1=0';
        if(!empty($cat))
            $url.= '&cat='.$cat;
        $params = array(
            'url_import' => $url,
            'page' => $page,
            'url' => 'https://scoresaber.com'
        );
        $songs = $this->getSongsFromDoc($params);
        return $songs;

    }

}