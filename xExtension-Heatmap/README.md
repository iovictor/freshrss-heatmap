# FreshRSS Heatmap Extension 🔥

A native extension for [FreshRSS](https://freshrss.org/) that automatically calculates and displays a "temperature" for every article in your feeds, based on the classic diminishing-returns algorithm created by Shaun Inman for Fever.

This extension helps you cut through the noise by highlighting articles that are highly upvoted or frequently shared across the web.

## How it works

Every article starts at a healthy baseline temperature of **98.6°F**.

When the extension loads an article, it checks three different sources to see if people are talking about it:
1. **Hacker News:** (via Algolia's open API)
2. **Reddit:** (via Reddit's public API)
3. **SearXNG:** (via a local SearXNG instance)

For every mention or upvote, the temperature rises. To prevent a single source (like one subreddit) from artificially inflating the score, the extension uses a **diminishing returns** formula (Score / 2^n). This means broad consensus across *different* subreddits or domains creates a much hotter article than one that is simply spammed in a single community.

### UI Indicators
The temperature is injected directly into your feed list and article view:
- 😊 **Normal** (98.6°F)
- 🤒 **Warm** (> 98.6°F)
- 🔥 **Hot** (> 101.0°F)
- 🌋 **Viral** (104.0°F+)

You can also click the floating **🔥 Sort by Heat** button to instantly reorder your current view by temperature!

## Installation

1. Copy this folder into your FreshRSS `extensions` directory:
   `.../freshrss/extensions/xExtension-Heatmap/`
2. Log into your FreshRSS account.
3. Go to **Configuration** > **Extensions**.
4. Enable the **Heatmap** extension.
5. *(Optional)* Edit the `credibility_multipliers.json` file to adjust domain weightings for the SearXNG integration.

## Configuration & Fallbacks

- **SearXNG:** By default, the extension looks for a SearXNG instance at `http://192.168.1.29:8080`. If you don't have one, or if the connection fails, the extension will **gracefully degrade** and simply ignore SearXNG scores without breaking the UI. To change the SearXNG URL, edit `Controllers/heatmapController.php`.
- **API Keys:** No API keys are required out of the box! We rely on public unauthenticated endpoints. If you hit rate limits with Reddit, you may need to implement authenticated requests in the PHP controller.
- **Cache:** Scores are cached locally in a SQLite database (`heatmap_cache.sqlite`) inside the extension folder for 24 hours to prevent spamming external APIs. Ensure your web server has write access to the extension directory.

## Contributing

Feel free to open PRs to add more data sources (Mastodon, GitHub, etc.) or improve the sorting UI!
