<?php

class HeatmapExtension extends Minz_Extension {
	
	public function init() {
		// Register hooks for UI injection
		Minz_Extension::registerHook('entry_before_display', [$this, 'injectHeatmapUI']);
		
		// TODO: Register hook for the list view
		
		// Ensure JS is loaded
		Minz_View::appendScript($this->getFileUrl('heatmap.js', 'js'));
		Minz_View::appendStyle($this->getFileUrl('heatmap.css', 'css'));
	}

	public function handleConfigureAction() {
		// Placeholder for configuration page
	}

	public function injectHeatmapUI($entry) {
		// Inject a placeholder badge into the entry content.
		// The JS will pick this up, read the data-url, and fetch the score.
		$url = $entry->link();
		$id = $entry->id();
		
		$badge = '<div class="heatmap-badge" data-url="' . htmlspecialchars($url) . '" data-id="' . $id . '">🌡️ Loading...</div>';
		
		// Prepend the badge to the content
		$entry->_content($badge . $entry->content());
		
		return $entry;
	}
}
