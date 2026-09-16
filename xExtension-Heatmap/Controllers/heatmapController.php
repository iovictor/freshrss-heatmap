<?php

class FreshExtension_heatmap_Controller extends Minz_ActionController {
	
	private $db;
	private $multipliers;
	
	public function firstAction(): void {
		// Ensure only logged in users can access this API
		if (!FreshRSS_Auth::hasAccess()) {
			Minz_Error::error(403);
			return;
		}
		
		$extPath = dirname(__DIR__);
		
		// Load credibility multipliers
		$multipliersPath = $extPath . '/credibility_multipliers.json';
		if (file_exists($multipliersPath)) {
			$this->multipliers = json_decode(file_get_contents($multipliersPath), true);
		} else {
			$this->multipliers = [];
		}
		
		// Initialize SQLite cache
		// IMPORTANT: Ensure the db file is writable by the web server
		$dbPath = $extPath . '/heatmap_cache.sqlite';
		$this->db = new PDO('sqlite:' . $dbPath);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->exec("CREATE TABLE IF NOT EXISTS cache (
			url TEXT PRIMARY KEY,
			score REAL,
			timestamp INTEGER
		)");
	}

	private function getCache($url) {
		$stmt = $this->db->prepare("SELECT score, timestamp FROM cache WHERE url = :url");
		$stmt->execute([':url' => $url]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($result && time() - $result['timestamp'] < 86400) {
			return floatval($result['score']);
		}
		return false;
	}

	private function setCache($url, $score) {
		$stmt = $this->db->prepare("INSERT INTO cache (url, score, timestamp) VALUES (:url, :score, :timestamp)
			ON CONFLICT(url) DO UPDATE SET score=excluded.score, timestamp=excluded.timestamp");
		$stmt->execute([
			':url' => $url,
			':score' => $score,
			':timestamp' => time()
		]);
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

	public function scoreAction(): void {
		$this->view->_layout(false);
		header('Content-Type: application/json');
		
		$url = Minz_Request::param('url', '');
		
		if (empty($url)) {
			echo json_encode(['error' => 'No URL provided']);
			return;
		}

		// 1. Check Cache
		$cachedScore = $this->getCache($url);
		if ($cachedScore !== false) {
			echo json_encode(['url' => $url, 'score' => round($cachedScore, 1), 'cached' => true]);
			return;
		}

		// FEVER MATH: Base Temp is 98.6, Scale is 1.25
		$degrees = 98.6;
		$scale = 1.25;
		$encodedUrl = urlencode($url);

		// 2. HackerNews (Algolia)
		$hnData = json_decode($this->fetchUrl("https://hn.algolia.com/api/v1/search?query={$encodedUrl}&restrictSearchableAttributes=url"), true);
		if (isset($hnData['hits'])) {
			$hnHits = $hnData['hits'];
			usort($hnHits, fn($a, $b) => intval($b['points']) <=> intval($a['points']));
			$weight = 0;
			foreach ($hnHits as $hit) {
				$points = intval($hit['points']);
				$post_score = log10($points + 10) * $scale;
				$degrees += $post_score / pow(2, $weight);
				$weight++;
			}
		}

		// 3. Reddit API
		$redditData = json_decode($this->fetchUrl("https://www.reddit.com/api/info.json?url={$encodedUrl}"), true);
		if (isset($redditData['data']['children'])) {
			$subreddits = [];
			foreach ($redditData['data']['children'] as $post) {
				$sub = $post['data']['subreddit'];
				$subreddits[$sub][] = intval($post['data']['ups']);
			}
			foreach ($subreddits as $sub => $upsList) {
				rsort($upsList);
				foreach ($upsList as $weight => $ups) {
					$post_score = log10($ups + 10) * $scale;
					$degrees += $post_score / pow(2, $weight);
				}
			}
		}

		// 4. SearXNG Local API
		$searxData = json_decode($this->fetchUrl("http://192.168.1.29:8080/search?q={$encodedUrl}&format=json"), true);
		if (isset($searxData['results'])) {
			$domains = [];
			foreach ($searxData['results'] as $result) {
				$host = parse_url($result['url'], PHP_URL_HOST);
				if ($host) {
					$host = strtolower(str_replace('www.', '', $host));
					$domains[$host][] = 1;
				}
			}
			
			foreach ($domains as $host => $mentions) {
				$credibility = $this->multipliers[$host] ?? 1.0;
				foreach ($mentions as $weight => $val) {
					$post_score = $scale * $credibility;
					$degrees += $post_score / pow(2, $weight);
				}
			}
		}

		$finalScore = round($degrees, 1);

		// Save to cache
		$this->setCache($url, $finalScore);

		echo json_encode([
			'url' => $url,
			'score' => $finalScore,
			'cached' => false
		]);
	}
}
