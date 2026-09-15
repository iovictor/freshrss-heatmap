<?php

class HeatmapExtension extends Minz_Extension {
	
	public function init() {
		// Register hook for UI injection
		Minz_Extension::registerHook('entry_before_display', [$this, 'injectHeatmapUI']);
		
		// Ensure JS and CSS are loaded
		Minz_View::appendScript($this->getFileUrl('heatmap.js', 'js'));
		Minz_View::appendStyle($this->getFileUrl('heatmap.css', 'css'));
	}

	public function handleConfigureAction() {
		// Placeholder for configuration page
	}

	public function injectHeatmapUI($entry) {
		$url = $entry->link();
		$id = $entry->id();
		
		// Badge injected into the title (visible in list view) and content (visible in reading view)
		$badge = '<span class="ext-heatmap-badge" data-url="' . htmlspecialchars($url) . '" data-id="' . $id . '">🌡️...</span> ';
		
		$entry->_title($badge . $entry->title());
		
		return $entry;
	}
}
