package short.drama.ronald.com.ui.home;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import short.drama.ronald.com.R;
import short.drama.ronald.com.adapter.DramaAdapter;
import short.drama.ronald.com.api.ApiClient;
import short.drama.ronald.com.model.Drama;
import short.drama.ronald.com.ui.detail.DetailActivity;

import java.lang.reflect.Type;
import java.util.List;

public class HomeFragment extends Fragment {

    private RecyclerView rvTrending;
    private RecyclerView rvLatest;
    private SwipeRefreshLayout swipeRefresh;
    private View progressBar;
    private View errorView;
    private TextView tvError;

    private DramaAdapter trendingAdapter;
    private DramaAdapter latestAdapter;
    private final Handler mainHandler = new Handler(Looper.getMainLooper());
    private final Gson gson = new Gson();

    private boolean trendingLoaded = false;
    private boolean latestLoaded = false;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_home, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        swipeRefresh = view.findViewById(R.id.swipe_refresh);
        progressBar = view.findViewById(R.id.progress_bar);
        errorView = view.findViewById(R.id.error_view);
        tvError = view.findViewById(R.id.tv_error);

        rvTrending = view.findViewById(R.id.rv_trending);
        rvLatest = view.findViewById(R.id.rv_latest);

        trendingAdapter = new DramaAdapter(requireContext());
        latestAdapter = new DramaAdapter(requireContext());

        rvTrending.setLayoutManager(new GridLayoutManager(requireContext(), 3));
        rvLatest.setLayoutManager(new GridLayoutManager(requireContext(), 3));

        rvTrending.setAdapter(trendingAdapter);
        rvLatest.setAdapter(latestAdapter);

        trendingAdapter.setOnItemClickListener(drama -> navigateToDetail(drama));
        latestAdapter.setOnItemClickListener(drama -> navigateToDetail(drama));

        swipeRefresh.setOnRefreshListener(() -> loadData(true));

        loadData(false);
    }

    private void loadData(boolean forceRefresh) {
        trendingLoaded = false;
        latestLoaded = false;
        showLoading();

        ApiClient client = ApiClient.getInstance(requireContext());

        client.fetchTrending(forceRefresh, new ApiClient.Callback() {
            @Override
            public void onSuccess(String json) {
                Type type = new TypeToken<List<Drama>>() {}.getType();
                List<Drama> dramas = gson.fromJson(json, type);
                mainHandler.post(() -> {
                    trendingAdapter.setData(dramas);
                    trendingLoaded = true;
                    checkBothLoaded();
                });
            }

            @Override
            public void onError(String message) {
                mainHandler.post(() -> {
                    trendingLoaded = true;
                    checkBothLoaded();
                    showToast("Trending: " + message);
                });
            }
        });

        client.fetchLatest(forceRefresh, new ApiClient.Callback() {
            @Override
            public void onSuccess(String json) {
                Type type = new TypeToken<List<Drama>>() {}.getType();
                List<Drama> dramas = gson.fromJson(json, type);
                mainHandler.post(() -> {
                    latestAdapter.setData(dramas);
                    latestLoaded = true;
                    checkBothLoaded();
                });
            }

            @Override
            public void onError(String message) {
                mainHandler.post(() -> {
                    latestLoaded = true;
                    checkBothLoaded();
                    showToast("Latest: " + message);
                });
            }
        });
    }

    private void checkBothLoaded() {
        if (trendingLoaded && latestLoaded) {
            hideLoading();
        }
    }

    private void showLoading() {
        if (!swipeRefresh.isRefreshing()) {
            progressBar.setVisibility(View.VISIBLE);
        }
        errorView.setVisibility(View.GONE);
    }

    private void hideLoading() {
        progressBar.setVisibility(View.GONE);
        swipeRefresh.setRefreshing(false);
    }

    private void showToast(String message) {
        if (isAdded()) {
            Toast.makeText(requireContext(), message, Toast.LENGTH_SHORT).show();
        }
    }

    private void navigateToDetail(Drama drama) {
        if (!isAdded()) return;
        Intent intent = new Intent(requireActivity(), DetailActivity.class);
        intent.putExtra(DetailActivity.EXTRA_BOOK_ID, drama.getBookId());
        intent.putExtra(DetailActivity.EXTRA_BOOK_NAME, drama.getBookName());
        intent.putExtra(DetailActivity.EXTRA_COVER_URL, drama.getCoverUrl());
        intent.putExtra(DetailActivity.EXTRA_INTRODUCTION, drama.getIntroduction());
        intent.putExtra(DetailActivity.EXTRA_PROTAGONIST, drama.getProtagonist());
        intent.putExtra(DetailActivity.EXTRA_CHAPTER_COUNT, drama.getChapterCount());
        startActivity(intent);
    }
}
