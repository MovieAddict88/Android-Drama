import json
import time
import logging
import os
import sys
import argparse

# Import the local API client
try:
    from dramawave_api_client import DramaWaveAPIClient
except ImportError:
    logging.error("dramawave_api_client.py not found in the current directory.")
    sys.exit(1)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler()]
)

class DramaWaveScraper:
    """
    A comprehensive scraper for mydramawave.com.
    Handles anonymous authentication, request signing, and response decryption.
    """

    def __init__(self, fetch_details=False):
        self.client = DramaWaveAPIClient()
        self.dramas = {}
        self.fetch_details = fetch_details

    def run(self, max_dramas=None):
        logging.info("Initializing DramaWave Scraper...")
        if not self.client.login_anonymous():
            logging.error("Failed to login anonymously. API might be down or protocol changed.")
            return

        # Step 1: Get the list of tabs (categories)
        logging.info("Retrieving category tabs...")
        tabs = self.client.request("/h5-api/homepage/v2/tab/list")
        if not tabs or tabs.get('code') != 200:
            logging.error("Failed to retrieve tab list.")
            return

        tab_list = tabs['data'].get('list', [])
        logging.info(f"Found {len(tab_list)} category tabs.")

        # Step 2: Iterate through each tab and extract dramas
        for tab in tab_list:
            tab_name = tab.get('name') or tab.get('business_name') or "Unknown"
            tab_key = tab.get('tab_key')
            logging.info(f"Processing category: {tab_name} (ID: {tab_key})")

            params = {
                "tab_key": tab_key,
                "position_index": tab.get('position_index', 0),
                "first": ""
            }
            index_res = self.client.request("/h5-api/homepage/v2/tab/index", params=params)

            if index_res and index_res.get('code') == 200:
                self._process_items(index_res['data'].get('items', []))

                page_info = index_res['data'].get('page_info', {})
                has_more = page_info.get('has_more', False)
                next_cursor = page_info.get('next', "")

                while has_more and next_cursor:
                    if max_dramas and len(self.dramas) >= max_dramas:
                        break

                    logging.info(f"Fetching next page for {tab_name} (cursor: {next_cursor})...")
                    feed_res = self.client.request("/h5-api/homepage/v2/tab/feed", method="POST", payload={
                        "tab_key": tab_key,
                        "next": next_cursor
                    })

                    if feed_res and feed_res.get('code') == 200:
                        self._process_items(feed_res['data'].get('items', []))
                        page_info = feed_res['data'].get('page_info', {})
                        has_more = page_info.get('has_more', False)
                        next_cursor = page_info.get('next', "")
                    else:
                        break

                    time.sleep(0.5)

            if max_dramas and len(self.dramas) >= max_dramas:
                break

        logging.info(f"Discovery phase complete. Found {len(self.dramas)} unique dramas.")

        if self.fetch_details:
            self._enrich_dramas()

        self.save_results()

    def _process_items(self, items):
        """Processes a list of modules or drama items."""
        for item in items:
            if 'items' in item and isinstance(item['items'], list):
                for sub_item in item['items']:
                    self._add_drama(sub_item)
            else:
                self._add_drama(item)

    def _add_drama(self, item):
        """Normalizes and adds a drama to the local collection."""
        if 'info' in item and isinstance(item['info'], dict):
            item = item['info']

        drama_id = item.get('key') or item.get('id')
        title = item.get('title') or item.get('name')

        if not drama_id or not title:
            return

        if drama_id not in self.dramas:
            self.dramas[drama_id] = {
                'id': drama_id,
                'title': title,
                'cover': item.get('cover'),
                'tags': item.get('tag', []),
                'content_tags': item.get('content_tags', []),
                'description': item.get('desc') or item.get('description', ''),
                'episode_count': item.get('episode_count'),
                'score': item.get('score'),
                'view_count': item.get('view_count'),
                'update_status': item.get('update_status'),
                'is_full_detail': False
            }

    def _enrich_dramas(self):
        """Fetches full metadata for each discovered drama."""
        logging.info("Enriching dramas with full details...")
        for i, (drama_id, drama) in enumerate(self.dramas.items(), 1):
            logging.info(f"[{i}/{len(self.dramas)}] Fetching details for: {drama['title']}")
            details = self.client.get_drama_info(drama_id)
            if details:
                drama.update({
                    'description': details.get('desc') or details.get('description', drama['description']),
                    'score': details.get('score', drama['score']),
                    'hot_score': details.get('hot_score'),
                    'share_url': details.get('share_url'),
                    'horizontal_cover': details.get('horizontal_cover'),
                    'actors': details.get('actors', []),
                    'performers': details.get('performers', []),
                    'is_full_detail': True
                })
                if 'episode_list' in details:
                    drama['episodes'] = [
                        {
                            'id': ep.get('id'),
                            'index': ep.get('index'),
                            'name': ep.get('name'),
                            'video_type': ep.get('video_type'),
                            'price': ep.get('episode_price')
                        } for ep in details['episode_list']
                    ]

            time.sleep(0.3)

    def save_results(self, filename="dramawave_data.json"):
        """Saves the scraped data to a JSON file."""
        output = list(self.dramas.values())
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(output, f, indent=2, ensure_ascii=False)
        logging.info(f"Successfully saved {len(output)} dramas to {filename}")

def main():
    parser = argparse.ArgumentParser(description="DramaWave Auto-Scraper")
    parser.add_argument("--limit", type=int, default=None, help="Maximum number of dramas to scrape")
    parser.add_argument("--details", action="store_true", help="Fetch full details (descriptions, episodes, etc.) for each drama")
    parser.add_argument("--output", type=str, default="dramawave_data.json", help="Output JSON filename")

    args = parser.parse_args()

    scraper = DramaWaveScraper(fetch_details=args.details)
    scraper.run(max_dramas=args.limit)

if __name__ == "__main__":
    main()
