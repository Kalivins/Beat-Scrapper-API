<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace BeatSaberScrapperApi;


class ProfileHandler extends BaseHandler {
	private function getRecentScoresPage(string $id, int $page = 1): \SimpleXMLElement {
		return $this->getHTML('/u/' . $id . '?sort=2&page=' . $page);
	}

	private function getTopRanksPage(string $id): \SimpleXMLElement {
		return $this->getHTML('/u/' . $id);
	}

	private function getGeneralProfilePage(string $id): \DOMDocument {
		return $this->getHTMLDom('/u/' . $id . '');
	}

	private function getUserID(string $name) {
		$id = '';
		$doc = $this->getHTMLDom('/global?search=' . $name .'');
		$idPath = new \DOMXPath($doc);
		$nodes = $idPath->query('/html/body/div/div/div/div/div[2]/div/div/table/tbody/tr/td[contains(@class, "player")]/a');
		if($nodes->length == 0)
			return;
		$id = explode('/', $nodes[0]->getAttribute('href'));
		$id = end($id);
		return $id;
	}

	/**
	 * @param \SimpleXMLElement $doc
	 * @return \Generator|Score[]
	 */
	private function getScoresFromDoc(\SimpleXMLElement $doc): \Generator {
		$rows = $doc->xpath("//table[contains(@class, 'songs')]/tbody/tr");
		if (!$rows) {
			return;
		}
		foreach ($rows as $row) {
			$rank = (int)substr(trim((string)$row->xpath("./th[contains(@class, 'rank')]")[0]), 1);
			$image = 'https://scoresaber.com/'.(string)$row->xpath(".//img[contains(@src, 'imports/images')]")[0]->attributes()->src;
			$id = (int)substr((string)$row->xpath(".//a[contains(@href, '/leaderboard/')]")[0]->attributes()->href, 13);
			$title = trim((string)$row->xpath(".//span[contains(@class, 'songTop pp')]")[0]);
			$mapper = (string)$row->xpath(".//span[contains(@class, 'songTop mapper')]")[0];
			$difficulty = (string)$row->xpath(".//span[contains(@class, 'songTop pp')]")[0]->children()[0];
			$time = new \DateTime((string)$row->xpath(".//span[contains(@class, 'time')]")[0]->attributes()->title);
			$accuracy = (string)$row->xpath(".//span[contains(@class, 'scoreBottom')]")[0];
			$pp = (float)(string)$row->xpath(".//span[contains(@class, 'ppValue')]")[0];
			$weightedPP = (float)substr((string)$row->xpath(".//span[contains(@class, 'ppWeightedValue')]")[0], 1);
			$linkDownload = SearchHandler::getSongDownload($title, $mapper);
			$song = new Song($id, $title, $difficulty, $image, $mapper, '', $linkDownload);
			yield new Score($song, $accuracy, $rank, $pp, $weightedPP, $time);
		}
	}

	/**
	 * @param string $id
	 * @return \Generator|Score[]
	 */
	public function getRecentScores(string $id, int $perpage): \Generator {
		$doc = $this->getRecentScoresPage($id, 1);
		// $maxPage = (int)$doc->xpath("//ul[contains(@class, 'pagination-list')]/li[last()]/a/text()")[0];
		// $currentPage = 1;
		$key = 0;
		if($key <= $perpage) {
			$scores = $this->getScoresFromDoc($doc);
			foreach ($scores as $score) {
				yield $score;
			}
			$key++;
		}
	}

	public function getTopRanks(string $id, int $perpage): \Generator {
		$doc = $this->getTopRanksPage($id);
		// $maxPage = (int)$doc->xpath("//ul[contains(@class, 'pagination-list')]/li[last()]/a/text()")[0];
		// $currentPage = 1;
		$key = 0;
		if($key <= $perpage) {
			$scores = $this->getScoresFromDoc($doc);
			foreach ($scores as $score) {
				yield $score;
			}
			$key++;
		}
	}

	public function checkUser($id) {
		if ($handle = opendir('imports/users/')) {

            while (false !== ($entry = readdir($handle))) {

				$fileId = explode('.', $entry);
				$fileId = reset($fileId);
        
                if ($fileId == $id) {
					
                    return true;

                }
            }
        
            closedir($handle);
		}
		
		return false;
	}

	public function getUserData($id) {
		$userData = file_get_contents('imports/users/'.$id.'.json');
		return json_decode($userData, true);
	}

	/**
	 * @param string $id
	 * @return json
	 */
	public function getGeneralProfile($name, $checkImport = false) {
		$content = array();
		$id = is_int($name) ? $name : $this->getUserID($name);
		if(!empty($id)) {
			if($checkImport && $this->checkUser($id)) {
				$data = $this->getUserData($id);
				$data['import'] = true;
				return $data; 
			}
			$doc = $this->getGeneralProfilePage($id);
		} else {
			return array('error' => 'Unable to get user ID');
		}
		$x_path = new \DOMXpath($doc);
		$descriptions = $x_path->query('/html/head/meta[@property="og:description"]/@content');
		$avatar = $x_path->query('/html/body/div/div/div/div[1]/div/div[1]/img');
		$name = $x_path->query('/html/body/div/div/div/div[1]/div/div[2]/h5/a');
		$content['resume'] = $descriptions[0]->value;
		$content['avatar'] = $avatar[0]->getAttribute('src');
		$content['name'] = trim($name[0]->nodeValue);
		$content['url'] = 'https://scoresaber.com/u/' . $id;
		$content['id'] = $id;

		return $content;
	}
}
