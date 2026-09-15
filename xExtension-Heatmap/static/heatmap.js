document.addEventListener('DOMContentLoaded', () => {
    const badges = document.querySelectorAll('.ext-heatmap-badge');
    
    // To avoid hammering the server, we process them in small batches
    const processBadges = async () => {
        for (let badge of badges) {
            // Only process if not already loaded
            if (badge.dataset.loaded) continue;
            
            const url = badge.dataset.url;
            if (!url) continue;

            try {
                // Fetch score from backend extension controller
                // The URL structure for FreshRSS extension controllers is:
                // ?c=heatmap&a=score&url=...
                const response = await fetch(`?c=heatmap&a=score&url=${encodeURIComponent(url)}`);
                const data = await response.json();
                
                if (data.score !== undefined) {
                    updateBadgeUI(badge, data.score);
                } else {
                    badge.innerText = '🌡️ Err';
                }
            } catch (e) {
                console.error("Heatmap extension error:", e);
                badge.innerText = '🌡️ Err';
            }
            
            badge.dataset.loaded = 'true';
        }
    };

    const updateBadgeUI = (badge, score) => {
        let emoji = '❄️';
        let className = 'ext-heatmap-cold';
        
        if (score >= 2000) {
            emoji = '🌋';
            className = 'ext-heatmap-viral';
        } else if (score >= 500) {
            emoji = '🔥';
            className = 'ext-heatmap-hot';
        } else if (score >= 50) {
            emoji = '😐';
            className = 'ext-heatmap-warm';
        }

        badge.className = `ext-heatmap-badge ${className}`;
        badge.innerText = `${emoji} ${score}`;
    };

    // Run after a short delay to ensure UI is fully interactive first
    setTimeout(processBadges, 500);
});
