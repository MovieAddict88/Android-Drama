# Shorty - Short Drama Streaming App

An Android application for watching short dramas from DramaBox, built entirely in Java.

## Package Name
`short.drama.ronald.com`

## Features
- 🔥 **Trending Dramas** - Browse the most trending short dramas
- 🆕 **Latest Dramas** - Discover newly added content
- 🔍 **Search** - Search for dramas by name
- 🎬 **Episode List** - View all episodes for any drama with thumbnails
- ▶️ **Video Player** - Built-in video player with episode navigation
- 💾 **Smart Caching** - Cache system to avoid repeated network requests
- 📱 **Dark Theme** - Beautiful dark-mode UI

## Tech Stack
- **Language**: Java only
- **Min SDK**: 21 (Android 5.0)
- **Target SDK**: 35 (Android 16)
- **Network**: OkHttp3
- **JSON Parsing**: Gson
- **Image Loading**: Glide
- **Video Playback**: ExoPlayer (Media3)
- **UI**: Material Components, RecyclerView, SwipeRefreshLayout

## API
Data is fetched from the DramaBox API via [sansekai proxy](https://api.sansekai.my.id/api/dramabox):

| Endpoint | Description |
|----------|-------------|
| `/trending` | Get trending dramas |
| `/latest` | Get latest dramas |
| `/search?query=<q>` | Search for dramas |
| `/allepisode?bookId=<id>` | Get all episodes for a drama |

### BookId Extraction
From a DramaBox URL like:
```
https://www.dramaboxdb.com/ep/41000122689_a-deal-with-my-billionaire-donor/602244888_Episode-1
```
The **bookId** is `41000122689` (the number before the underscore in the first path segment after `/ep/`).

## Caching
- Trending/Latest: cached for **30 minutes**
- Episodes: cached for **10 minutes**
- Pull-to-refresh forces a fresh network call
