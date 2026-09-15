# FreshRSS Heatmap Extension 🔥

A native FreshRSS extension that calculates a "heat" and "credibility" score for RSS articles. Instead of treating all RSS feed items equally, this extension cross-references URLs against HackerNews, Reddit, and a local SearXNG instance to determine how viral or credible an article is, giving you a visual heatmap of your feed!

## Features

- **Asynchronous Scoring:** Uses the `entry_before_display` hook to inject lightweight JS into your feed. The heavy lifting (API calls) is done asynchronously so your feed loads instantly.
- **Visual Heatmap:** Easily spot viral or highly-rated articles with dynamic UI emojis in both the post list and the reading view.
  - ❄️ **Cold** (< 50 points)
  - 😐 **Warm** (50 - 500 points)
  - 🔥 **Hot** (500 - 2000 points)
  - 🌋 **Viral** (2000+ points)
- **Credibility Engine:** 
  - HackerNews (Algolia) upvotes/comments integration.
  - Reddit cross-post and upvote integration.
  - SearXNG web-mention aggregation with domain-based credibility multipliers.
- **SQLite Caching:** Scores are cached locally for 24 hours to prevent hitting external API rate limits.

## Installation

1. Clone this repository or download the `xExtension-Heatmap` folder.
2. Place the `xExtension-Heatmap` folder into your FreshRSS extensions directory (usually `./data/www/FreshRSS/extensions/` or `./extensions/`).
3. Log into your FreshRSS dashboard, navigate to **Extensions**, and enable the **Heatmap** extension.

## Development / Agentic Workflow

This repository is designed with autonomous AI agents in mind. Please refer to:
- `agents.md` for subagent responsibilities and strict constraints (e.g., non-blocking UI rules).
- `goal.md` for the overarching project architecture and success criteria.

## License

MIT License
