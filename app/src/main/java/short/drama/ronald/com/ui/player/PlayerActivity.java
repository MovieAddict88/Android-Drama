package short.drama.ronald.com.ui.player;

import android.os.Bundle;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.media3.common.MediaItem;
import androidx.media3.common.Player;
import androidx.media3.exoplayer.ExoPlayer;
import androidx.media3.ui.PlayerView;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import short.drama.ronald.com.R;
import short.drama.ronald.com.adapter.EpisodeListAdapter;

import java.util.ArrayList;
import java.util.List;

public class PlayerActivity extends AppCompatActivity {

    public static final String EXTRA_VIDEO_URL = "video_url";
    public static final String EXTRA_EPISODE_NAME = "episode_name";
    public static final String EXTRA_BOOK_NAME = "book_name";
    public static final String EXTRA_EPISODE_INDEX = "episode_index";
    public static final String EXTRA_ALL_URLS = "all_urls";
    public static final String EXTRA_ALL_NAMES = "all_names";

    private PlayerView playerView;
    private ExoPlayer player;
    private TextView tvEpisodeTitle;
    private RecyclerView rvEpisodeList;
    private EpisodeListAdapter episodeListAdapter;

    private List<String> allUrls;
    private List<String> allNames;
    private int currentIndex;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_player);

        playerView = findViewById(R.id.player_view);
        tvEpisodeTitle = findViewById(R.id.tv_episode_title);
        rvEpisodeList = findViewById(R.id.rv_episode_list);

        allUrls = getIntent().getStringArrayListExtra(EXTRA_ALL_URLS);
        allNames = getIntent().getStringArrayListExtra(EXTRA_ALL_NAMES);
        currentIndex = getIntent().getIntExtra(EXTRA_EPISODE_INDEX, 0);
        String episodeName = getIntent().getStringExtra(EXTRA_EPISODE_NAME);

        if (allUrls == null) allUrls = new ArrayList<>();
        if (allNames == null) allNames = new ArrayList<>();

        tvEpisodeTitle.setText(episodeName != null ? episodeName : "");

        episodeListAdapter = new EpisodeListAdapter(allNames, currentIndex);
        rvEpisodeList.setLayoutManager(new LinearLayoutManager(this));
        rvEpisodeList.setAdapter(episodeListAdapter);
        episodeListAdapter.setOnItemClickListener(position -> {
            String url = position < allUrls.size() ? allUrls.get(position) : null;
            if (url == null || url.isEmpty()) {
                Toast.makeText(this, "Episode not available", Toast.LENGTH_SHORT).show();
                return;
            }
            currentIndex = position;
            String name = position < allNames.size() ? allNames.get(position) : "";
            tvEpisodeTitle.setText(name);
            episodeListAdapter.setCurrentIndex(currentIndex);
            playUrl(url);
            scrollToCurrentEpisode();
        });

        rvEpisodeList.scrollToPosition(currentIndex);

        initPlayer();
        String videoUrl = getIntent().getStringExtra(EXTRA_VIDEO_URL);
        if (videoUrl != null && !videoUrl.isEmpty()) {
            playUrl(videoUrl);
        }
    }

    private void initPlayer() {
        player = new ExoPlayer.Builder(this).build();
        playerView.setPlayer(player);

        player.addListener(new Player.Listener() {
            @Override
            public void onPlaybackStateChanged(int state) {
                if (state == Player.STATE_ENDED) {
                    playNext();
                }
            }
        });
    }

    private void playUrl(String url) {
        if (player == null) return;
        MediaItem mediaItem = MediaItem.fromUri(url);
        player.setMediaItem(mediaItem);
        player.prepare();
        player.play();
    }

    private void playNext() {
        int nextIndex = currentIndex + 1;
        if (nextIndex < allUrls.size()) {
            String url = allUrls.get(nextIndex);
            if (url != null && !url.isEmpty()) {
                currentIndex = nextIndex;
                String name = nextIndex < allNames.size() ? allNames.get(nextIndex) : "";
                tvEpisodeTitle.setText(name);
                episodeListAdapter.setCurrentIndex(currentIndex);
                playUrl(url);
                scrollToCurrentEpisode();
            }
        }
    }

    private void scrollToCurrentEpisode() {
        rvEpisodeList.scrollToPosition(currentIndex);
    }

    @Override
    protected void onPause() {
        super.onPause();
        if (player != null) player.pause();
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (player != null) player.play();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (player != null) {
            player.release();
            player = null;
        }
    }
}
