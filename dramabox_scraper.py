import requests
import json
import re
from typing import List, Dict, Any, Optional

class DramaBoxScraper:
    def __init__(self):
        self.base_url = "https://www.dramabox.com"
        self.api_base_url = "https://dramabox.sansekai.my.id/api/dramabox"
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept": "application/json, text/plain, */*",
            "Accept-Language": "en-US,en;q=0.9",
        }

    def _get_next_data(self, url: str) -> Optional[Dict[str, Any]]:
        try:
            response = requests.get(url, headers=self.headers, timeout=10)
            if response.status_code != 200:
                print(f"Error fetching {url}: {response.status_code}")
                return None

            match = re.search(r'<script id="__NEXT_DATA__" type="application/json">(.*?)</script>', response.text)
            if match:
                return json.loads(match.group(1))
            return None
        except Exception as e:
            print(f"Exception fetching {url}: {e}")
            return None

    def get_trending_official(self) -> List[Dict[str, Any]]:
        """Fetch trending dramas from official website."""
        data = self._get_next_data(self.base_url)
        if not data:
            return []

        try:
            # Navigate through Next.js data structure
            # This might change if they update their site
            sections = data.get("props", {}).get("pageProps", {}).get("initialState", {}).get("home", {}).get("sections", [])
            for section in sections:
                if section.get("name") == "Trending":
                    return section.get("list", [])
            return []
        except KeyError:
            return []

    def search(self, query: str) -> List[Dict[str, Any]]:
        """Search for dramas using the sansekai API."""
        url = f"{self.api_base_url}/search"
        params = {"query": query}
        try:
            response = requests.get(url, headers=self.headers, params=params, timeout=10)
            if response.status_code == 200:
                return response.json()
            return []
        except Exception as e:
            print(f"Error in search: {e}")
            return []

    def get_trending(self) -> List[Dict[str, Any]]:
        """Fetch trending dramas using the sansekai API."""
        url = f"{self.api_base_url}/trending"
        try:
            response = requests.get(url, headers=self.headers, timeout=10)
            if response.status_code == 200:
                return response.json()
            return []
        except Exception as e:
            print(f"Error in get_trending: {e}")
            return []

    def get_popular_search(self) -> List[Dict[str, Any]]:
        """Fetch popular search terms using the sansekai API."""
        url = f"{self.api_base_url}/populersearch"
        try:
            response = requests.get(url, headers=self.headers, timeout=10)
            if response.status_code == 200:
                return response.json()
            return []
        except Exception as e:
            print(f"Error in get_popular_search: {e}")
            return []

    def get_details(self, book_id: str) -> Dict[str, Any]:
        """Fetch drama details using the sansekai API."""
        url = f"{self.api_base_url}/detail"
        params = {"bookId": book_id}
        try:
            response = requests.get(url, headers=self.headers, params=params, timeout=10)
            if response.status_code == 200:
                return response.json()
            return {}
        except Exception as e:
            print(f"Error in get_details: {e}")
            return {}

    def get_episodes(self, book_id: str) -> List[Dict[str, Any]]:
        """Fetch all episodes for a drama using the sansekai API."""
        url = f"{self.api_base_url}/allepisode"
        params = {"bookId": book_id}
        try:
            response = requests.get(url, headers=self.headers, params=params, timeout=10)
            if response.status_code == 200:
                return response.json()
            return []
        except Exception as e:
            print(f"Error in get_episodes: {e}")
            return []

if __name__ == "__main__":
    scraper = DramaBoxScraper()

    print("--- Trending Dramas ---")
    trending = scraper.get_trending()
    for drama in trending[:5]:
        print(f"ID: {drama.get('bookId')}, Name: {drama.get('bookName')}")

    if trending:
        book_id = trending[0].get('bookId')
        print(f"\n--- Details for {book_id} ---")
        details = scraper.get_details(book_id)
        print(f"Name: {details.get('bookName')}")
        print(f"Intro: {details.get('introduction')[:100]}...")

        print(f"\n--- Episodes for {book_id} ---")
        episodes = scraper.get_episodes(book_id)
        print(f"Total Episodes: {len(episodes)}")
        if episodes:
            first_ep = episodes[0]
            print(f"First Episode: {first_ep.get('chapterName')}")
            # The video links are usually in cdnList
            cdn_list = first_ep.get('cdnList', [])
            if cdn_list:
                video_paths = cdn_list[0].get('videoPathList', [])
                if video_paths:
                    print(f"Video URL (720p): {video_paths[0].get('videoPath')}")

    print("\n--- Search: 'pewaris' ---")
    search_results = scraper.search("pewaris")
    for drama in search_results[:5]:
        print(f"ID: {drama.get('bookId')}, Name: {drama.get('bookName')}")
