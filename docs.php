<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SΛNSΞKΛI API Documentation (Mimic)</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://api.sansekai.my.id/favicon.png" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Full Spec Extracted from original API
            const spec = {
                openapi: "3.0.0",
                info: {
                    title: "SΛNSΞKΛI API Documentation (Mimic)",
                    description: "PHP/HTML Implementation of the Sansekai API functionality.<br>Mimicking all endpoints for local development.",
                    version: "1.0.0",
                },
                servers: [
                    { url: "/api", description: "Local Mimic Server" }
                ],
                paths: {
                    "/dramabox/foryou": { get: { tags: ["DramaBox"], summary: "For You", parameters: [{ name: "page", in: "query", schema: { type: "integer" } }], responses: { 200: { description: "Success" } } } },
                    "/dramabox/vip": { get: { tags: ["DramaBox"], summary: "VIP Page", responses: { 200: { description: "Success" } } } },
                    "/dramabox/dubindo": { get: { tags: ["DramaBox"], summary: "Dub Indo", parameters: [{ name: "classify", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/dramabox/randomdrama": { get: { tags: ["DramaBox"], summary: "Random Drama", responses: { 200: { description: "Success" } } } },
                    "/dramabox/latest": { get: { tags: ["DramaBox"], summary: "Latest", responses: { 200: { description: "Success" } } } },
                    "/dramabox/trending": { get: { tags: ["DramaBox"], summary: "Trending", responses: { 200: { description: "Success" } } } },
                    "/dramabox/populersearch": { get: { tags: ["DramaBox"], summary: "Popular Search", responses: { 200: { description: "Success" } } } },
                    "/dramabox/search": { get: { tags: ["DramaBox"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/dramabox/detail": { get: { tags: ["DramaBox"], summary: "Detail", parameters: [{ name: "bookId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/dramabox/allepisode": { get: { tags: ["DramaBox"], summary: "All Episodes", parameters: [{ name: "bookId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/reelshort/foryou": { get: { tags: ["ReelShort"], summary: "For You", parameters: [{ name: "page", in: "query", schema: { type: "integer" } }], responses: { 200: { description: "Success" } } } },
                    "/reelshort/homepage": { get: { tags: ["ReelShort"], summary: "Homepage", responses: { 200: { description: "Success" } } } },
                    "/reelshort/search": { get: { tags: ["ReelShort"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/reelshort/detail": { get: { tags: ["ReelShort"], summary: "Detail", parameters: [{ name: "bookId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/reelshort/episode": { get: { tags: ["ReelShort"], summary: "Episode", parameters: [{ name: "bookId", in: "query", required: true, schema: { type: "string" } }, { name: "episodeNumber", in: "query", required: true, schema: { type: "integer" } }], responses: { 200: { description: "Success" } } } },
                    "/shortmax/foryou": { get: { tags: ["ShortMax"], summary: "For You", responses: { 200: { description: "Success" } } } },
                    "/shortmax/latest": { get: { tags: ["ShortMax"], summary: "Latest", responses: { 200: { description: "Success" } } } },
                    "/shortmax/rekomendasi": { get: { tags: ["ShortMax"], summary: "Recommended", responses: { 200: { description: "Success" } } } },
                    "/shortmax/vip": { get: { tags: ["ShortMax"], summary: "VIP", responses: { 200: { description: "Success" } } } },
                    "/shortmax/search": { get: { tags: ["ShortMax"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/shortmax/detail": { get: { tags: ["ShortMax"], summary: "Detail", parameters: [{ name: "shortPlayId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/shortmax/allepisode": { get: { tags: ["ShortMax"], summary: "All Episodes", parameters: [{ name: "shortPlayId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/shortmax/hls": { get: { tags: ["ShortMax"], summary: "HLS Proxy", parameters: [{ name: "url", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/netshort/foryou": { get: { tags: ["NetShort"], summary: "For You", responses: { 200: { description: "Success" } } } },
                    "/netshort/theaters": { get: { tags: ["NetShort"], summary: "Theaters", responses: { 200: { description: "Success" } } } },
                    "/netshort/search": { get: { tags: ["NetShort"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/netshort/allepisode": { get: { tags: ["NetShort"], summary: "All Episodes", parameters: [{ name: "shortPlayId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/melolo/foryou": { get: { tags: ["Melolo"], summary: "For You", responses: { 200: { description: "Success" } } } },
                    "/melolo/latest": { get: { tags: ["Melolo"], summary: "Latest", responses: { 200: { description: "Success" } } } },
                    "/melolo/trending": { get: { tags: ["Melolo"], summary: "Trending", responses: { 200: { description: "Success" } } } },
                    "/melolo/search": { get: { tags: ["Melolo"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/melolo/detail": { get: { tags: ["Melolo"], summary: "Detail", parameters: [{ name: "bookId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/melolo/stream": { get: { tags: ["Melolo"], summary: "Stream", parameters: [{ name: "videoId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/flickreels/foryou": { get: { tags: ["FlickReels"], summary: "For You", responses: { 200: { description: "Success" } } } },
                    "/flickreels/latest": { get: { tags: ["FlickReels"], summary: "Latest", responses: { 200: { description: "Success" } } } },
                    "/flickreels/hotrank": { get: { tags: ["FlickReels"], summary: "Hot Rank", responses: { 200: { description: "Success" } } } },
                    "/flickreels/search": { get: { tags: ["FlickReels"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/flickreels/detailAndAllEpisode": { get: { tags: ["FlickReels"], summary: "Detail/Episodes", parameters: [{ name: "id", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/freereels/foryou": { get: { tags: ["FreeReels"], summary: "For You", responses: { 200: { description: "Success" } } } },
                    "/freereels/homepage": { get: { tags: ["FreeReels"], summary: "Homepage", responses: { 200: { description: "Success" } } } },
                    "/freereels/animepage": { get: { tags: ["FreeReels"], summary: "Anime Page", responses: { 200: { description: "Success" } } } },
                    "/freereels/search": { get: { tags: ["FreeReels"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/freereels/detailAndAllEpisode": { get: { tags: ["FreeReels"], summary: "Detail/Episodes", parameters: [{ name: "key", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/anime/latest": { get: { tags: ["Anime"], summary: "Latest", responses: { 200: { description: "Success" } } } },
                    "/anime/recommended": { get: { tags: ["Anime"], summary: "Recommended", responses: { 200: { description: "Success" } } } },
                    "/anime/search": { get: { tags: ["Anime"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/anime/detail": { get: { tags: ["Anime"], summary: "Detail", parameters: [{ name: "urlId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/anime/movie": { get: { tags: ["Anime"], summary: "Movie", responses: { 200: { description: "Success" } } } },
                    "/anime/getvideo": { get: { tags: ["Anime"], summary: "Get Video", parameters: [{ name: "chapterUrlId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/komik/recommended": { get: { tags: ["Komik"], summary: "Recommended", parameters: [{ name: "type", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/komik/latest": { get: { tags: ["Komik"], summary: "Latest", parameters: [{ name: "type", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/komik/search": { get: { tags: ["Komik"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/komik/popular": { get: { tags: ["Komik"], summary: "Popular", responses: { 200: { description: "Success" } } } },
                    "/komik/detail": { get: { tags: ["Komik"], summary: "Detail", parameters: [{ name: "manga_id", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/komik/chapterlist": { get: { tags: ["Komik"], summary: "Chapter List", parameters: [{ name: "manga_id", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/komik/getimage": { get: { tags: ["Komik"], summary: "Get Image", parameters: [{ name: "chapter_id", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/moviebox/homepage": { get: { tags: ["MovieBox"], summary: "Homepage", responses: { 200: { description: "Success" } } } },
                    "/moviebox/trending": { get: { tags: ["MovieBox"], summary: "Trending", responses: { 200: { description: "Success" } } } },
                    "/moviebox/search": { get: { tags: ["MovieBox"], summary: "Search", parameters: [{ name: "query", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/moviebox/detail": { get: { tags: ["MovieBox"], summary: "Detail", parameters: [{ name: "subjectId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/moviebox/sources": { get: { tags: ["MovieBox"], summary: "Sources", parameters: [{ name: "subjectId", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/moviebox/generate-link-stream-video": { get: { tags: ["MovieBox"], summary: "Stream Link", parameters: [{ name: "url", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/ai/chatgpt": { get: { tags: ["Artificial Intelligence (AI)"], summary: "ChatGPT", parameters: [{ name: "prompt", in: "query", required: true, schema: { type: "string" } }], responses: { 200: { description: "Success" } } } },
                    "/uploader": { post: { tags: ["Uploader"], summary: "Upload File", requestBody: { content: { "multipart/form-data": { schema: { type: "object", properties: { file: { type: "string", format: "binary" } } } } } }, responses: { 200: { description: "Success" } } } }
                }
            };

            const ui = SwaggerUIBundle({
                spec: spec,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout"
            });
            window.ui = ui;
        };
    </script>
</body>
</html>
