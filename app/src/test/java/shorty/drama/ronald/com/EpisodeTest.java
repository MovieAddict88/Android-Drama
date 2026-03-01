package shorty.drama.ronald.com;

import org.junit.Test;
import static org.junit.Assert.*;
import java.util.ArrayList;
import java.util.List;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

public class EpisodeTest {
    @Test
    public void testUrlConstruction() {
        String json = "[{\"chapterId\":\"123\",\"cdnList\":[{\"cdnDomain\":\"hwztvideo.com\",\"videoPathList\":[{\"quality\":720,\"videoPath\":\"/v1/1.mp4\",\"isDefault\":1}]}]}]";
        Gson gson = new Gson();
        java.lang.reflect.Type listType = new TypeToken<List<Episode>>() {}.getType();
        List<Episode> episodes = gson.fromJson(json, listType);

        assertNotNull(episodes);
        assertEquals(1, episodes.size());
        assertEquals("https://hwztvideo.com/v1/1.mp4", episodes.get(0).getDefaultVideoUrl());
    }

    @Test
    public void testAbsoluteUrl() {
        String json = "[{\"chapterId\":\"123\",\"cdnList\":[{\"cdnDomain\":\"hwztvideo.com\",\"videoPathList\":[{\"quality\":720,\"videoPath\":\"https://other.com/v2/2.mp4\",\"isDefault\":1}]}]}]";
        Gson gson = new Gson();
        java.lang.reflect.Type listType = new TypeToken<List<Episode>>() {}.getType();
        List<Episode> episodes = gson.fromJson(json, listType);

        assertNotNull(episodes);
        assertEquals("https://other.com/v2/2.mp4", episodes.get(0).getDefaultVideoUrl());
    }
}
