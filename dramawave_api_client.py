import requests
import hashlib
import time
import base64
import json
import logging
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad, unpad

class DramaWaveAPIClient:
    BASE_URL = "https://api.mydramawave.com"
    AES_KEY = "2r36789f45q01ae5"
    APP_SECRET_PREFIX = "8IAcbWyCsVhYv82S2eofRqK1DF3nNDAv"

    def __init__(self, user_agent=None):
        self.ua = user_agent or "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1"
        self.device_id = self._generate_device_id()
        self.auth_data = None
        self.session = requests.Session()
        self.session.headers.update({
            "User-Agent": self.ua,
            "Content-Type": "application/json",
            "app-name": "com.dramawave.h5",
            "app-version": "1.6.20",
            "device": "h5",
            "device-hash": self.device_id,
            "device-id": self.device_id,
        })

    def _generate_device_id(self):
        # Simulation of index.js: br.md5(navigator.userAgent + "random_16_chars")
        # Using a fixed "random" part for consistency across sessions or a real random one
        random_part = "a1b2c3d4e5f6g7h8"
        return hashlib.md5((self.ua + random_part).encode('utf-8')).hexdigest()

    def _get_aes_key(self):
        key_bytes = self.AES_KEY.encode('utf-8')
        if len(key_bytes) < 16:
            res = bytearray(16)
            res[:len(key_bytes)] = key_bytes
            return bytes(res)
        return key_bytes[:16]

    def encrypt(self, text):
        key = self._get_aes_key()
        cipher = AES.new(key, AES.MODE_CBC)
        iv = cipher.iv
        ct_bytes = cipher.encrypt(pad(text.encode('utf-8'), AES.block_size))
        return base64.b64encode(iv + ct_bytes).decode('utf-8')

    def decrypt(self, b64_text):
        key = self._get_aes_key()
        raw = base64.b64decode(b64_text)
        iv = raw[:16]
        ct = raw[16:]
        cipher = AES.new(key, AES.MODE_CBC, iv=iv)
        pt = unpad(cipher.decrypt(ct), AES.block_size)
        return pt.decode('utf-8')

    def _get_auth_header(self):
        ts = int(time.time() * 1000)
        sig = ""
        token = ""
        if self.auth_data:
            sig_input = f"{self.APP_SECRET_PREFIX}&{self.auth_data['auth_secret']}"
            sig = hashlib.md5(sig_input.encode('utf-8')).hexdigest()
            token = self.auth_data['auth_key']
        return f"oauth_signature={sig},oauth_token={token},ts={ts}"

    def login_anonymous(self):
        payload = {"device_id": self.device_id}
        # Login usually doesn't need the auth header yet, but it does need the payload encrypted
        enc_payload = self.encrypt(json.dumps(payload))
        url = f"{self.BASE_URL}/h5-api/anonymous/login"

        # Override headers for login (remove auth if not present)
        headers = self.session.headers.copy()
        headers["authorization"] = self._get_auth_header()

        response = self.session.post(url, data=enc_payload, headers=headers)
        if response.status_code == 200:
            try:
                # Login response can be encrypted too
                try:
                    data = response.json()
                except:
                    data = json.loads(self.decrypt(response.text))

                if data.get('code') == 200:
                    self.auth_data = data['data']
                    return True
            except Exception as e:
                logging.error(f"Login failed to parse: {e}")
        return False

    def request(self, path, method="GET", payload=None, params=None):
        if not self.auth_data and not path.endswith("login"):
            if not self.login_anonymous():
                raise Exception("Authentication failed")

        url = f"{self.BASE_URL}{path}"
        headers = self.session.headers.copy()
        headers["authorization"] = self._get_auth_header()

        if method == "POST" and payload:
            data = self.encrypt(json.dumps(payload))
            response = self.session.post(url, data=data, headers=headers, params=params)
        else:
            response = self.session.get(url, headers=headers, params=params)

        if response.status_code == 200:
            try:
                try:
                    return response.json()
                except:
                    return json.loads(self.decrypt(response.text))
            except Exception as e:
                logging.error(f"Request {path} failed to decrypt: {e}")
                return None
        else:
            logging.error(f"Request {path} returned status {response.status_code}")
            return None


    def get_drama_info(self, series_id):
        # GET /h5-api/drama/info?series_id=...
        params = {"series_id": series_id}
        res = self.request("/h5-api/drama/info", params=params)
        if res and res.get('code') == 200:
            return res['data'].get('info')
        return None
