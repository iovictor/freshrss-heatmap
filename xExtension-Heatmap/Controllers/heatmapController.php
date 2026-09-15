<?php

class FreshExtension_Heatmap_Controller extends Minz_ActionController {
	
	private $cacheFile;

	public function firstAction() {
		// Ensure only logged in users can access this API
		if (!Minz_Session::param('currentUser', false)) {
			Minz_Error::error(403);
			return;
		}
		$extPath = Minz_ExtensionManager::getExtension('Heatmap')->getPath();
		$this->cacheFile = $extPath . '/heatmap_cache.json';
	}

	private function getCache() {
		if (file_exists($this->cacheFile)) {
			$data = json_decode(file_get_contents($this->cacheFile), true);
			return is_array($data) ? $data : [];
		}
		return [];
	}

	private function setCache($cache) {
		file_put_contents($this->cacheFile, json_encode($cache));
	}

	private function fetchUrl($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_USERAGENT, 'FreshRSS-Heatmap-Extension/1.0');
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	public function scoreAction() {
		$this->view->_layout(false);
		header('Content-Type: application/json');
		
		$url = Minz_Request::param('url', '');
		
		if (empty($url)) {
			echo json_encode(['error' => 'No URL provided']);
			return;
		}

		// 1. Check Cache
		$cache = $this->getCache();
		if (isset($cache[$url]) && time() - $cache[$url]['timestamp'] < 86400) {
			echo json_encode(['url' => $url, 'score' => $cache[$url]['score'], 'cached' => true]);
			return;
		}

		$score = 0;
		$encodedUrl = urlencode($url);

		// 2. HackerNews (Algolia)
		$hnData = json_decode($this->fetchUrl("https://hn.algolia.com/api/v1/search?query={$encodedUrl}&restrictSearchableAttributes=url"), true);
		if (isset($hnData['hits'])) {
			foreach ($hnData['hits'] as $hit) {
				$score += intval($hit['points']);
			}
		}

		// 3. Reddit API
		$redditData = json_decode($this->fetchUrl("https://www.reddit.com/api/info.json?url={$encodedUrl}"), true);
		if (isset($redditData['data']['children'])) {
			foreach ($redditData['data']['children'] as $post) {
				$score += (intval($post['data']['ups']) * 0.5); // Reddit upvotes are worth 0.5 points
			}
		}

		// 4. SearXNG Local API (Mentions across the web)
		$searxData = json_decode($this->fetchUrl("http://192.168.1.29:8080/search?q={$encodedUrl}&format=json"), true);
		if (isset($searxData['results'])) {
			// Add 10 points for every distinct search result (mention) found on the web
			$score += (count($searxData['results']) * 10);
		}

		// 5. Credibility Multipliers
		$host = parse_url($url, PHP_URL_HOST);
		if ($host) {
			$host = strtolower(str_replace('www.', '', $host));
			$highCredibility = ['reuters.com', 'apnews.com', 'github.com', 'bloomberg.com', 'bbc.com', 'npr.org'];
			$lowCredibility = ['thesun.co.uk', 'dailymail.co.uk', 'nypost.com', 'foxnews.com', 'buzzfeed.com'];

			if (in_array($host, $highCredibility)) {
				$score = $score * 1.5;
			} elseif (in_array($host, $lowCredibility)) {
				$score = $score * 0.2;
			}
		}

		$finalScore = round($score);

		// Save to cache
		$cache[$url] = [
			'score' => $finalScore,
			'timestamp' => time()
		];
		$this->setCache($cache);

		echo json_encode([
			'url' => $url,
			'score' => $finalScore,
			'cached' => false
		]);
	}
}
