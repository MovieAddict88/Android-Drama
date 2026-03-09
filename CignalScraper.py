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
            response = self.session.get(self.STOREFRONT_URL, headers=self.headers)
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
            response = self.session.get(url, headers=self.headers)
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
                        "type": entry.get("cty"),
                        "watch_url": f"https://www.cignalplay.com/watch/{entry.get('id')}"
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
    unique_channels_list = list({c['id']: c for c in channels}.values())

    # Save to JSON
    with open('channels.json', 'w') as jf:
        json.dump(unique_channels_list, jf, indent=2)
    logging.info(f"Saved {len(unique_channels_list)} channels to channels.json")

    # Generate HTML
    html_content = f"""
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CignalPlay Live TV Channels</title>
        <style>
            body {{ font-family: sans-serif; background: #121212; color: #eee; padding: 20px; }}
            table {{ width: 100%; border-collapse: collapse; margin-top: 20px; }}
            th, td {{ padding: 12px; text-align: left; border-bottom: 1px solid #333; }}
            th {{ background-color: #1f1f1f; color: #ffcc00; }}
            tr:hover {{ background-color: #1a1a1a; }}
            .badge {{ padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }}
            .badge-fhd {{ background: #ffcc00; color: #000; }}
            .badge-sd {{ background: #666; color: #fff; }}
        </style>
    </head>
    <body>
        <h1>CignalPlay Live TV Channels</h1>
        <p>Total unique channels: {len(unique_channels_list)}</p>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Quality</th>
                    <th>Watch Page</th>
                </tr>
            </thead>
            <tbody>
    """
    for ch in unique_channels_list:
        quality_class = "badge-fhd" if ch['quality'] == "FHD" else "badge-sd"
        html_content += f"""
                <tr>
                    <td><strong>{ch['name']}</strong></td>
                    <td>{ch['category']}</td>
                    <td><span class="badge {quality_class}">{ch['quality']}</span></td>
                    <td><a href="{ch['watch_url']}" target="_blank" style="color: #ffcc00; font-size: 0.9em;">Play on CignalPlay</a></td>
                </tr>
        """
    html_content += """
            </tbody>
        </table>
    </body>
    </html>
    """
    with open('channels.html', 'w') as hf:
        hf.write(html_content)
    logging.info(f"Generated channels.html")
