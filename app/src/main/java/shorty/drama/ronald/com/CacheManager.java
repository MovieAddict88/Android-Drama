package shorty.drama.ronald.com;

import android.content.Context;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.InputStreamReader;

public class CacheManager {
    private static final String CACHE_FILE_PREFIX = "episodes_";

    public static void saveEpisodes(Context context, String bookId, String json) {
        try {
            File file = new File(context.getCacheDir(), CACHE_FILE_PREFIX + bookId);
            FileOutputStream fos = new FileOutputStream(file);
            fos.write(json.getBytes());
            fos.close();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    public static String getEpisodes(Context context, String bookId) {
        try {
            File file = new File(context.getCacheDir(), CACHE_FILE_PREFIX + bookId);
            if (!file.exists()) return null;

            FileInputStream fis = new FileInputStream(file);
            BufferedReader reader = new BufferedReader(new InputStreamReader(fis));
            StringBuilder sb = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                sb.append(line);
            }
            reader.close();
            return sb.toString();
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }
}
