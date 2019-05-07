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

namespace ScoreSaberApi;

class BaseHandler {
	private $baseUrl;

	public function __construct(string $baseUrl = 'https://scoresaber.com') {
		$this->baseUrl = $baseUrl;
	}

	protected function getPage(string $page) {
		$page = trim($page, '/');
		return file_get_contents($this->baseUrl . '/' . $page);
	}

	protected function getHTML(string $page): \SimpleXMLElement {
		libxml_use_internal_errors(true);
		$html = new \DOMDocument();
		$html->loadHTML($this->getPage($page));
		return simplexml_import_dom($html);
	}

	protected function getHTMLDom(string $page): \DOMDocument {
		libxml_use_internal_errors(true);
		$html = new \DOMDocument();
		$html->loadHTML($this->getPage($page));
		return $html;
	}
}
