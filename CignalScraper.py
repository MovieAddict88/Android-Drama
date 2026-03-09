import requests
import json
import logging

logging.basicConfig(level=logging.INFO, format='%(message)s')

class CignalScraper:
    STOREFRONT_URL = "https://storefront-cdn.api.pldt.firstlight.ai/storefront/list?reg=ph&dt=web&client=pldt-cignal-web"
    CONTENT_BASE_PARAMS = "&client=pldt-cignal-web&reg=ph&dt=web"

    def __init__(self):
        self.session = requests.Session()
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept": "application/json"
        }

    def fetch_storefront(self):
        logging.info("Fetching Storefront...")
        try:
            response = self.session.get(self.STOREFRONT_URL, headers=self.headers, verify=False)
            if response.status_code == 200:
                return response.json()
            else:
                logging.error(f"Storefront error: {response.status_code}")
        except Exception as e:
            logging.error(f"Failed to fetch storefront: {e}")
        return None

    def get_live_tv_channels(self):
        storefront = self.fetch_storefront()
        if not storefront or not storefront.get("data"):
            return []

        # Find the LIVE TV tab
        tabs = storefront["data"][0].get("t", [])
        live_tv_tab = next((t for t in tabs if t.get("lon") and t.get("lon")[0].get("n") == "LIVE TV"), None)

        if not live_tv_tab:
            logging.error("LIVE TV tab not found.")
            return []

        all_channels = []
        for container in live_tv_tab.get("c", []):
            container_name = container.get("lon")[0].get("n") if container.get("lon") else "Unknown"
            logging.info(f"Processing Category: {container_name}")

            for item in container.get("i", []):
                content_url = item.get("cu")
                if content_url:
                    # Fix parameters for detail view
                    if "&client=" not in content_url:
                        content_url += self.CONTENT_BASE_PARAMS

                    channels = self.fetch_content_details(content_url, container_name)
                    all_channels.extend(channels)

        return all_channels

    def fetch_content_details(self, url, category):
        try:
            response = self.session.get(url, headers=self.headers, verify=False)
            if response.status_code == 200:
                data = response.json()
                channels = []
                for entry in data.get("data", []):
                    channel_info = {
                        "name": entry.get("lon")[0].get("n") if entry.get("lon") else "Unnamed",
                        "id": entry.get("id"),
                        "category": category,
                        "description": entry.get("log")[0].get("n")[0] if entry.get("log") else "",
                        "quality": entry.get("vq"),
                        "type": entry.get("cty")
                    }
                    channels.append(channel_info)
                return channels
            else:
                logging.error(f"Content detail error: {response.status_code} for {url}")
        except Exception as e:
            logging.error(f"Failed to fetch content details: {e}")
        return []

if __name__ == "__main__":
    scraper = CignalScraper()
    channels = scraper.get_live_tv_channels()

    # Remove duplicates by ID
    unique_channels = {c['id']: c for c in channels}.values()

    print(json.dumps(list(unique_channels), indent=2))
    logging.info(f"\nTotal unique channels found: {len(unique_channels)}")
