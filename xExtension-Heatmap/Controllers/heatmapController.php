<?php

class FreshExtension_Heatmap_Controller extends Minz_ActionController {
	
	public function firstAction() {
		// Ensure only logged in users can access this API
		if (!Minz_Session::param('currentUser', false)) {
			Minz_Error::error(403);
			return;
		}
	}

	public function scoreAction() {
		$this->view->_layout(false);
		header('Content-Type: application/json');
		
		$url = Minz_Request::param('url', '');
		
		if (empty($url)) {
			echo json_encode(['error' => 'No URL provided']);
			return;
		}
		
		// TODO: Implement SQLite caching
		// TODO: Query HackerNews API
		// TODO: Query Reddit API
		// TODO: Query SearXNG API
		
		// For the UI testing PR, return a random score
		$score = rand(0, 3000); 
		
		echo json_encode([
			'url' => $url,
			'score' => $score
		]);
	}
}
