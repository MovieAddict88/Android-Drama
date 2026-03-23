# Scraping Feasibility Analysis

This report documents the scraping feasibility for `byseraguci.com` and `dramasnacker.com`.

---

## 1. byseraguci.com (Byse / VMX CDN)

### Analysis Summary
Scraping all movies from `https://byseraguci.com/e/` is currently **not feasible** for the following reasons:

#### Lack of Public Index
- The website acts as a private content delivery network (CDN) for platforms like **VMX Dashboard**.
- The path `/e/` is used for individual video embeds via unlisted, hashed URLs (e.g., `/e/[file_code]`).
- Direct access to `/e/` or common listing paths returns 404 or 403 errors.

#### Private Ecosystem
- Content discovery happens within a password-protected dashboard.
- Specific movies (e.g., "Tuklas" 2026, ID: 1652947) are managed externally and served via unlisted links.

#### Technical Barriers
- Built as a Vite-based SPA with Cloudflare protection.
- Data access requires a user-specific **API Key** for endpoints like `/file/list`.

---

## 2. dramasnacker.com

### Analysis Summary
Scraping complete episodes and video sources from `https://www.dramasnacker.com/` is **technically complex and highly restricted** for the following reasons:

#### Encrypted API Responses
- The platform uses a central API (`api.dramasnacker.com`).
- Critical data, such as episode lists (`/chapter/list`) and video details (`/chapter/load`), is returned as **encrypted strings**.
- Decryption logic is obfuscated within client-side JavaScript, requiring reverse engineering of the AES/RSA implementations found in the site's bundles.

#### Protected Video Sources
- Video URLs are protected by **CloudFront Signed URLs**.
- These URLs include `Signature`, `Expires`, and `Key-Pair-Id` parameters, making them time-limited and likely tied to an authenticated session.
- Accessing full video content usually requires a valid user account with sufficient "coins" or "bonuses," as indicated by the user info API.

#### Discovery & Automation
- While a list of drama titles can be seen on the homepage, obtaining the full episode list and high-quality video sources requires bypassing the API encryption and session-based signatures.

---

## 3. Framework Considerations (Nuxt / Next.js)

Using a different frontend framework like **Nuxt 3** or **Next.js** does not make these API endpoints more "available."

- **The Limitation is Backend-Driven**: The protection (encryption and authentication) happens on the server side (`api.dramasnacker.com` and `api.byse.sx`). A frontend framework is just a consumer of data.
- **Keys and Secrets**: Even if you build a new frontend in Next.js, your code would still need the **Private API Keys** or the **Client-Side Decryption Logic** to read the data. These are not publicly exposed.
- **Cloudflare & CORS**: These APIs are protected by Cloudflare and have strict Cross-Origin Resource Sharing (CORS) policies. They will reject requests that do not originate from their official apps or authorized environments.

## Final Conclusion
Scraping "all movies/episodes" and "video sources" from these sites is **not feasible** using standard public scraping methods, regardless of the framework used.

- **byseraguci.com**: Requires an authorized API key and a pre-existing list of file codes.
- **dramasnacker.com**: Requires reverse-engineering the API encryption and handling session-based CloudFront signatures, likely alongside an authenticated premium account.
