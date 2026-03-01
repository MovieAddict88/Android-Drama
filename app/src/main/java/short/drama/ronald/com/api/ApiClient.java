package short.drama.ronald.com.api;

import android.content.Context;

import short.drama.ronald.com.cache.CacheManager;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

public class ApiClient {

    private static final String BASE_URL = "https://api.sansekai.my.id/api/dramabox";
    private static ApiClient instance;
    private final OkHttpClient httpClient;
    private final CacheManager cacheManager;

    private ApiClient(Context context) {
        httpClient = new OkHttpClient.Builder()
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(20, TimeUnit.SECONDS)
                .writeTimeout(15, TimeUnit.SECONDS)
                .build();
        cacheManager = CacheManager.getInstance(context);
    }

    public static synchronized ApiClient getInstance(Context context) {
        if (instance == null) {
            instance = new ApiClient(context.getApplicationContext());
        }
        return instance;
    }

    public interface Callback {
        void onSuccess(String json);
        void onError(String message);
    }

    public void fetchLatest(boolean forceRefresh, Callback callback) {
        String cacheKey = "latest";
        if (!forceRefresh) {
            String cached = cacheManager.getJson(cacheKey);
            if (cached != null) {
                callback.onSuccess(cached);
                return;
            }
        }
        fetchAndCache(BASE_URL + "/latest", cacheKey, callback);
    }

    public void fetchTrending(boolean forceRefresh, Callback callback) {
        String cacheKey = "trending";
        if (!forceRefresh) {
            String cached = cacheManager.getJson(cacheKey);
            if (cached != null) {
                callback.onSuccess(cached);
                return;
            }
        }
        fetchAndCache(BASE_URL + "/trending", cacheKey, callback);
    }

    public void search(String query, Callback callback) {
        try {
            String encodedQuery = java.net.URLEncoder.encode(query, "UTF-8");
            String url = BASE_URL + "/search?query=" + encodedQuery;
            fetchRaw(url, callback);
        } catch (java.io.UnsupportedEncodingException e) {
            callback.onError("Encoding error");
        }
    }

    public void fetchEpisodes(String bookId, boolean forceRefresh, Callback callback) {
        if (!forceRefresh) {
            String cached = cacheManager.getEpisodesJson(bookId);
            if (cached != null) {
                callback.onSuccess(cached);
                return;
            }
        }
        String url = BASE_URL + "/allepisode?bookId=" + bookId;
        fetchAndCacheEpisodes(url, bookId, callback);
    }

    private void fetchAndCache(String url, String cacheKey, Callback callback) {
        fetchRaw(url, new Callback() {
            @Override
            public void onSuccess(String json) {
                cacheManager.putJson(cacheKey, json);
                callback.onSuccess(json);
            }

            @Override
            public void onError(String message) {
                callback.onError(message);
            }
        });
    }

    private void fetchAndCacheEpisodes(String url, String bookId, Callback callback) {
        fetchRaw(url, new Callback() {
            @Override
            public void onSuccess(String json) {
                cacheManager.putEpisodesJson(bookId, json);
                callback.onSuccess(json);
            }

            @Override
            public void onError(String message) {
                callback.onError(message);
            }
        });
    }

    private void fetchRaw(String url, Callback callback) {
        new Thread(() -> {
            try {
                Request request = new Request.Builder()
                        .url(url)
                        .addHeader("User-Agent", "Shorty/1.0 Android")
                        .build();
                try (Response response = httpClient.newCall(request).execute()) {
                    if (!response.isSuccessful()) {
                        callback.onError("Server error: " + response.code());
                        return;
                    }
                    String body = response.body() != null ? response.body().string() : null;
                    if (body == null || body.isEmpty()) {
                        callback.onError("Empty response");
                        return;
                    }
                    callback.onSuccess(body);
                }
            } catch (IOException e) {
                callback.onError("Network error: " + e.getMessage());
            }
        }).start();
    }
}
