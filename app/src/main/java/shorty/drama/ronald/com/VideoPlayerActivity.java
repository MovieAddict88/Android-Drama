package shorty.drama.ronald.com;

import android.media.MediaPlayer;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.MediaController;
import android.widget.ProgressBar;
import android.widget.Toast;
import android.widget.VideoView;
import androidx.appcompat.app.AppCompatActivity;

public class VideoPlayerActivity extends AppCompatActivity {
    private VideoView videoView;
    private ProgressBar progressBar;
    private String videoUrl;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_video_player);

        videoView = findViewById(R.id.videoView);
        progressBar = findViewById(R.id.videoProgressBar);

        videoUrl = getIntent().getStringExtra("video_url");
        String episodeName = getIntent().getStringExtra("episode_name");

        if (videoUrl == null) {
            Toast.makeText(this, "Video URL is missing", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        MediaController mediaController = new MediaController(this);
        mediaController.setAnchorView(videoView);
        videoView.setMediaController(mediaController);

        progressBar.setVisibility(View.VISIBLE);
        videoView.setVideoURI(Uri.parse(videoUrl));

        videoView.setOnPreparedListener(mp -> {
            progressBar.setVisibility(View.GONE);
            videoView.start();
        });

        videoView.setOnErrorListener((mp, what, extra) -> {
            progressBar.setVisibility(View.GONE);
            Toast.makeText(VideoPlayerActivity.this, "Error playing video: " + what, Toast.LENGTH_SHORT).show();
            return false;
        });

        videoView.setOnCompletionListener(mp -> {
            // Optional: Auto-play next episode logic could go here
        });
    }

    @Override
    protected void onPause() {
        super.onPause();
        if (videoView.isPlaying()) {
            videoView.pause();
        }
    }
}
