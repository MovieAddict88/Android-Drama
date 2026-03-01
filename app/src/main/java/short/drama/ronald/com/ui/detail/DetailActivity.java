package short.drama.ronald.com.ui.detail;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import short.drama.ronald.com.R;
import short.drama.ronald.com.adapter.EpisodeAdapter;
import short.drama.ronald.com.api.ApiClient;
import short.drama.ronald.com.model.Episode;
import short.drama.ronald.com.ui.player.PlayerActivity;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class DetailActivity extends AppCompatActivity {

    public static final String EXTRA_BOOK_ID = "book_id";
    public static final String EXTRA_BOOK_NAME = "book_name";
    public static final String EXTRA_COVER_URL = "cover_url";
    public static final String EXTRA_INTRODUCTION = "introduction";
    public static final String EXTRA_PROTAGONIST = "protagonist";
    public static final String EXTRA_CHAPTER_COUNT = "chapter_count";

    private ImageView ivCover;
    private TextView tvTitle;
    private TextView tvProtagonist;
    private TextView tvChapterCount;
    private TextView tvIntroduction;
    private RecyclerView rvEpisodes;
    private View progressBar;
    private View errorView;
    private TextView tvError;
    private View btnRetry;

    private EpisodeAdapter episodeAdapter;
    private final Handler mainHandler = new Handler(Looper.getMainLooper());
    private final Gson gson = new Gson();

    private String bookId;
    private List<Episode> episodeList = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_detail);

        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }

        ivCover = findViewById(R.id.iv_cover);
        tvTitle = findViewById(R.id.tv_title);
        tvProtagonist = findViewById(R.id.tv_protagonist);
        tvChapterCount = findViewById(R.id.tv_chapter_count);
        tvIntroduction = findViewById(R.id.tv_introduction);
        rvEpisodes = findViewById(R.id.rv_episodes);
        progressBar = findViewById(R.id.progress_bar);
        errorView = findViewById(R.id.error_view);
        tvError = findViewById(R.id.tv_error);
        btnRetry = findViewById(R.id.btn_retry);

        bookId = getIntent().getStringExtra(EXTRA_BOOK_ID);
        String bookName = getIntent().getStringExtra(EXTRA_BOOK_NAME);
        String coverUrl = getIntent().getStringExtra(EXTRA_COVER_URL);
        String introduction = getIntent().getStringExtra(EXTRA_INTRODUCTION);
        String protagonist = getIntent().getStringExtra(EXTRA_PROTAGONIST);
        int chapterCount = getIntent().getIntExtra(EXTRA_CHAPTER_COUNT, 0);

        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle(bookName);
        }

        tvTitle.setText(bookName);
        tvChapterCount.setText(chapterCount + " Episodes");
        tvIntroduction.setText(introduction != null ? introduction : "");
        tvProtagonist.setText(protagonist != null ? "Starring: " + protagonist : "");

        Glide.with(this)
                .load(coverUrl)
                .placeholder(R.drawable.ic_placeholder)
                .into(ivCover);

        episodeAdapter = new EpisodeAdapter();
        rvEpisodes.setLayoutManager(new LinearLayoutManager(this));
        rvEpisodes.setAdapter(episodeAdapter);

        episodeAdapter.setOnEpisodeClickListener((episode, position) -> {
            if (episode.isCharged()) {
                Toast.makeText(this, "This episode requires a subscription", Toast.LENGTH_SHORT).show();
                return;
            }
            String videoUrl = episode.getBestVideoUrl();
            if (videoUrl == null || videoUrl.isEmpty()) {
                Toast.makeText(this, "Video not available", Toast.LENGTH_SHORT).show();
                return;
            }
            Intent intent = new Intent(this, PlayerActivity.class);
            intent.putExtra(PlayerActivity.EXTRA_VIDEO_URL, videoUrl);
            intent.putExtra(PlayerActivity.EXTRA_EPISODE_NAME, episode.getChapterName());
            intent.putExtra(PlayerActivity.EXTRA_BOOK_NAME, bookId);
            intent.putExtra(PlayerActivity.EXTRA_EPISODE_INDEX, position);
            ArrayList<String> videoUrls = new ArrayList<>();
            ArrayList<String> episodeNames = new ArrayList<>();
            for (Episode ep : episodeList) {
                String url = ep.getBestVideoUrl();
                videoUrls.add(url != null ? url : "");
                episodeNames.add(ep.getChapterName());
            }
            intent.putStringArrayListExtra(PlayerActivity.EXTRA_ALL_URLS, videoUrls);
            intent.putStringArrayListExtra(PlayerActivity.EXTRA_ALL_NAMES, episodeNames);
            startActivity(intent);
        });

        btnRetry.setOnClickListener(v -> loadEpisodes(false));

        loadEpisodes(false);
    }

    private void loadEpisodes(boolean forceRefresh) {
        progressBar.setVisibility(View.VISIBLE);
        errorView.setVisibility(View.GONE);

        ApiClient.getInstance(this).fetchEpisodes(bookId, forceRefresh, new ApiClient.Callback() {
            @Override
            public void onSuccess(String json) {
                Type type = new TypeToken<List<Episode>>() {}.getType();
                List<Episode> episodes = gson.fromJson(json, type);
                mainHandler.post(() -> {
                    progressBar.setVisibility(View.GONE);
                    if (episodes != null) {
                        episodeList = episodes;
                        episodeAdapter.setData(episodes);
                    }
                });
            }

            @Override
            public void onError(String message) {
                mainHandler.post(() -> {
                    progressBar.setVisibility(View.GONE);
                    errorView.setVisibility(View.VISIBLE);
                    tvError.setText("Failed to load episodes: " + message);
                });
            }
        });
    }

    @Override
    public boolean onSupportNavigateUp() {
        onBackPressed();
        return true;
    }
}
