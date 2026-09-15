<?php

class FreshExtension_heatmap_Controller extends Minz_ActionController {
	
	public function firstAction() {
		// Ensure only logged in users can access this API
		if (!Minz_Session::param('currentUser', false)) {
			Minz_Error::error(403);
			return;
		}
	}

	public function scoreAction() {
		Minz_View::appendStyle('display: none;'); // We are returning JSON
		$url = Minz_Request::param('url', '');
		
		if (empty($url)) {
			echo json_encode(['error' => 'No URL provided']);
			return;
		}
		
		// TODO: Implement SQLite caching
		// TODO: Query HackerNews API
		// TODO: Query Reddit API
		// TODO: Query SearXNG API
		
		$score = rand(0, 5000); // Dummy score for testing
		
		echo json_encode([
			'url' => $url,
			'score' => $score
		]);
	}
}
