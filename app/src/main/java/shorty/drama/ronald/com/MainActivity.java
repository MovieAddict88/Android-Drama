package shorty.drama.ronald.com;

import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.io.IOException;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;
import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;
import android.content.Intent;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class MainActivity extends AppCompatActivity implements EpisodeAdapter.OnEpisodeClickListener {
    private RecyclerView recyclerView;
    private ProgressBar progressBar;
    private EditText bookIdInput;
    private Button btnLoad;
    private EpisodeAdapter adapter;
    private List<Episode> episodeList = new ArrayList<>();
    private final OkHttpClient client = new OkHttpClient();
    private final String API_URL = "https://api.sansekai.my.id/api/dramabox/allepisode?bookId=";
    private String currentBookId = "41000122689"; // Default bookId as per example

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        recyclerView = findViewById(R.id.recyclerView);
        progressBar = findViewById(R.id.progressBar);
        bookIdInput = findViewById(R.id.bookIdInput);
        btnLoad = findViewById(R.id.btnLoad);

        recyclerView.setLayoutManager(new LinearLayoutManager(this));
        adapter = new EpisodeAdapter(episodeList, this);
        recyclerView.setAdapter(adapter);

        btnLoad.setOnClickListener(v -> {
            String input = bookIdInput.getText().toString().trim();
            if (!input.isEmpty()) {
                currentBookId = extractBookId(input);
                loadEpisodes(currentBookId);
            } else {
                Toast.makeText(MainActivity.this, "Please enter a Book ID or URL", Toast.LENGTH_SHORT).show();
            }
        });

        loadEpisodes(currentBookId);
    }

    private String extractBookId(String input) {
        // Handle URLs like https://www.dramaboxdb.com/ep/41000122689_a-deal-with-my-billionaire-donor/602244888_Episode-1
        // The user says 41000122689 is the bookId in one part and 602244888 is the bookId in another part of the prompt.
        // Actually looking at the URL: 41000122689 is the series ID and 602244888 is the episode ID.
        // The API uses bookId=41000122689 to list all episodes.

        if (input.startsWith("http")) {
            // Pattern to match the number after /ep/ and before the first underscore or slash
            Pattern pattern = Pattern.compile("/ep/(\\d+)");
            Matcher matcher = pattern.matcher(input);
            if (matcher.find()) {
                return matcher.group(1);
            }
        }
        return input; // Assume it's already a Book ID if not a URL
    }

    private void loadEpisodes(String bookId) {
        String cachedJson = CacheManager.getEpisodes(this, bookId);
        if (cachedJson != null) {
            parseAndDisplay(cachedJson);
            fetchEpisodes(bookId, true); // Silent update in background
        } else {
            fetchEpisodes(bookId, false);
        }
    }

    private void fetchEpisodes(final String bookId, final boolean silent) {
        if (!silent) {
            episodeList.clear();
            adapter.notifyDataSetChanged();
            progressBar.setVisibility(View.VISIBLE);
        }

        Request request = new Request.Builder()
                .url(API_URL + bookId)
                .build();

        client.newCall(request).enqueue(new Callback() {
            @Override
            public void onFailure(Call call, IOException e) {
                new Handler(Looper.getMainLooper()).post(() -> {
                    if (!silent) {
                        progressBar.setVisibility(View.GONE);
                        Toast.makeText(MainActivity.this, "Failed to fetch episodes: " + e.getMessage(), Toast.LENGTH_SHORT).show();
                    }
                });
            }

            @Override
            public void onResponse(Call call, Response response) throws IOException {
                final String json = response.body().string();
                if (response.isSuccessful()) {
                    CacheManager.saveEpisodes(MainActivity.this, bookId, json);
                    new Handler(Looper.getMainLooper()).post(() -> {
                        progressBar.setVisibility(View.GONE);
                        parseAndDisplay(json);
                    });
                } else {
                    new Handler(Looper.getMainLooper()).post(() -> {
                        if (!silent) {
                            progressBar.setVisibility(View.GONE);
                            Toast.makeText(MainActivity.this, "Server error: " + response.code(), Toast.LENGTH_SHORT).show();
                        }
                    });
                }
            }
        });
    }

    private void parseAndDisplay(String json) {
        try {
            Gson gson = new Gson();
            Type listType = new TypeToken<List<Episode>>() {}.getType();
            List<Episode> newEpisodes = gson.fromJson(json, listType);
            if (newEpisodes != null) {
                episodeList.clear();
                episodeList.addAll(newEpisodes);
                adapter.notifyDataSetChanged();
                if (episodeList.isEmpty()) {
                    Toast.makeText(this, "No episodes found for this ID", Toast.LENGTH_SHORT).show();
                }
            }
        } catch (Exception e) {
            Toast.makeText(this, "Error parsing data", Toast.LENGTH_SHORT).show();
        }
    }

    @Override
    public void onEpisodeClick(Episode episode) {
        String videoUrl = episode.getDefaultVideoUrl();
        if (videoUrl != null) {
            Intent intent = new Intent(this, VideoPlayerActivity.class);
            intent.putExtra("video_url", videoUrl);
            intent.putExtra("episode_name", episode.getChapterName());
            startActivity(intent);
        } else {
            Toast.makeText(this, "No video available for this episode", Toast.LENGTH_SHORT).show();
        }
    }
}
