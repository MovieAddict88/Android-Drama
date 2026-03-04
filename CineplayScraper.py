import requests
import json
import logging

logging.basicConfig(level=logging.INFO, format='%(levelname)s: %(message)s')

class CineplayScraper:
    """
    Scraper for cineplay-freelivetv.online.
    Currently, the site is suspended and returns 403 Forbidden.
    This script is designed to attempt extraction once access is restored.
    """

    BASE_URL = "https://cineplay-freelivetv.online"
    ENDPOINT = f"{BASE_URL}/Pornhub.php"

    def __init__(self):
        self.session = requests.Session()
        # Default headers that are common for these types of aggregators
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept": "application/json, text/javascript, */*; q=0.01",
            "X-Requested-With": "XMLHttpRequest",
            "Referer": self.BASE_URL + "/"
        }

    def check_status(self):
        """Checks the current availability of the endpoint."""
        try:
            response = self.session.get(self.ENDPOINT, headers=self.headers, timeout=10)
            logging.info(f"Status Code: {response.status_code}")

            if response.status_code == 200:
                logging.info(f"Response Body: {response.text}")
                data = response.json()
                if data.get("s") == 1:
                    logging.info("Access successful!")
                    self.extract_source(data)
                    return data
                else:
                    logging.warning(f"API Error: {data.get('m', 'Unknown error')}")
            elif response.status_code == 403:
                logging.error("Access Forbidden (403). The site may be restricted by IP, headers, or is suspended.")
                return 403
            else:
                logging.error(f"Server returned status {response.status_code}")
                logging.info(f"Response Body: {response.text}")
        except Exception as e:
            logging.error(f"Request failed: {e}")
        return None

    def extract_source(self, data):
        """
        Placeholder for source extraction logic.
        Once access is granted, we look for keys like 'url', 'link', or 'src'.
        """
        # Based on typical aggregator responses
        source = data.get("url") or data.get("link") or data.get("src")
        if source:
            logging.info(f"Extracted Source: {source}")
        else:
            logging.warning("No direct source found in the response. Full data structure:")
            logging.info(json.dumps(data, indent=2))

    def probe_headers(self):
        """Attempts to find a bypass for the 403 Forbidden error."""
        test_uas = [
            "Dalvik/2.1.0 (Linux; U; Android 11; Pixel 5)",
            "okhttp/4.9.0",
            "com.cineplay.app/1.0.0",
            "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15"
        ]

        for ua in test_uas:
            logging.info(f"Probing with User-Agent: {ua}")
            headers = self.headers.copy()
            headers["User-Agent"] = ua
            try:
                resp = self.session.get(self.ENDPOINT, headers=headers, timeout=5)
                if resp.status_code == 200 and '"s":1' in resp.text:
                    logging.info(f"FOUND WORKING USER-AGENT: {ua}")
                    return ua
            except:
                continue
        return None

if __name__ == "__main__":
    scraper = CineplayScraper()
    logging.info("Checking Cineplay endpoint status...")
    status = scraper.check_status()

    if status == 403:
        logging.info("Attempting to probe for a bypass...")
        working_ua = scraper.probe_headers()
        if working_ua:
            logging.info(f"Found bypass with User-Agent: {working_ua}")
        else:
            logging.error("Could not find a bypass with standard mobile User-Agents.")
