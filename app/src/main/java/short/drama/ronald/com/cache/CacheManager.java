package short.drama.ronald.com.cache;

import android.content.Context;
import android.content.SharedPreferences;

import java.util.HashMap;
import java.util.Map;

public class CacheManager {

    private static final String PREF_NAME = "shorty_cache";
    private static final long CACHE_DURATION_MS = 30 * 60 * 1000;
    private static final long EPISODES_CACHE_DURATION_MS = 10 * 60 * 1000;

    private static CacheManager instance;
    private final SharedPreferences prefs;
    private final Map<String, Object> memoryCache = new HashMap<>();
    private final Map<String, Long> memoryCacheTimestamps = new HashMap<>();

    private CacheManager(Context context) {
        prefs = context.getApplicationContext().getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
    }

    public static synchronized CacheManager getInstance(Context context) {
        if (instance == null) {
            instance = new CacheManager(context);
        }
        return instance;
    }

    public void putJson(String key, String json) {
        prefs.edit()
                .putString(key, json)
                .putLong(key + "_ts", System.currentTimeMillis())
                .apply();
    }

    public String getJson(String key) {
        long ts = prefs.getLong(key + "_ts", 0);
        if (System.currentTimeMillis() - ts > CACHE_DURATION_MS) return null;
        return prefs.getString(key, null);
    }

    public void putEpisodesJson(String bookId, String json) {
        String key = "episodes_" + bookId;
        prefs.edit()
                .putString(key, json)
                .putLong(key + "_ts", System.currentTimeMillis())
                .apply();
    }

    public String getEpisodesJson(String bookId) {
        String key = "episodes_" + bookId;
        long ts = prefs.getLong(key + "_ts", 0);
        if (System.currentTimeMillis() - ts > EPISODES_CACHE_DURATION_MS) return null;
        return prefs.getString(key, null);
    }

    public void putMemory(String key, Object value) {
        memoryCache.put(key, value);
        memoryCacheTimestamps.put(key, System.currentTimeMillis());
    }

    public Object getMemory(String key) {
        Long ts = memoryCacheTimestamps.get(key);
        if (ts == null || System.currentTimeMillis() - ts > CACHE_DURATION_MS) return null;
        return memoryCache.get(key);
    }

    public void clearAll() {
        prefs.edit().clear().apply();
        memoryCache.clear();
        memoryCacheTimestamps.clear();
    }
}
