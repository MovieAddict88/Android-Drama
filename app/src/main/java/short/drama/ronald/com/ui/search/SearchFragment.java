package short.drama.ronald.com.ui.search;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.KeyEvent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.inputmethod.EditorInfo;
import android.view.inputmethod.InputMethodManager;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import short.drama.ronald.com.R;
import short.drama.ronald.com.adapter.DramaAdapter;
import short.drama.ronald.com.api.ApiClient;
import short.drama.ronald.com.model.Drama;
import short.drama.ronald.com.ui.detail.DetailActivity;

import java.lang.reflect.Type;
import java.util.List;

public class SearchFragment extends Fragment {

    private EditText etSearch;
    private RecyclerView rvResults;
    private View progressBar;
    private TextView tvEmpty;
    private DramaAdapter adapter;
    private final Handler mainHandler = new Handler(Looper.getMainLooper());
    private final Gson gson = new Gson();
    private Runnable searchRunnable;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_search, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        etSearch = view.findViewById(R.id.et_search);
        rvResults = view.findViewById(R.id.rv_results);
        progressBar = view.findViewById(R.id.progress_bar);
        tvEmpty = view.findViewById(R.id.tv_empty);

        adapter = new DramaAdapter(requireContext());
        rvResults.setLayoutManager(new GridLayoutManager(requireContext(), 3));
        rvResults.setAdapter(adapter);

        adapter.setOnItemClickListener(drama -> navigateToDetail(drama));

        etSearch.setOnEditorActionListener((v, actionId, event) -> {
            if (actionId == EditorInfo.IME_ACTION_SEARCH ||
                    (event != null && event.getKeyCode() == KeyEvent.KEYCODE_ENTER)) {
                performSearch(etSearch.getText().toString().trim());
                hideKeyboard();
                return true;
            }
            return false;
        });

        etSearch.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                if (searchRunnable != null) mainHandler.removeCallbacks(searchRunnable);
                String query = s.toString().trim();
                if (query.length() >= 2) {
                    searchRunnable = () -> performSearch(query);
                    mainHandler.postDelayed(searchRunnable, 700);
                }
            }

            @Override
            public void afterTextChanged(Editable s) {}
        });
    }

    private void performSearch(String query) {
        if (query.isEmpty()) return;

        progressBar.setVisibility(View.VISIBLE);
        tvEmpty.setVisibility(View.GONE);
        adapter.setData(null);

        ApiClient.getInstance(requireContext()).search(query, new ApiClient.Callback() {
            @Override
            public void onSuccess(String json) {
                mainHandler.post(() -> {
                    progressBar.setVisibility(View.GONE);
                    try {
                        Type type = new TypeToken<List<Drama>>() {}.getType();
                        List<Drama> results = gson.fromJson(json, type);
                        if (results == null || results.isEmpty()) {
                            tvEmpty.setVisibility(View.VISIBLE);
                            tvEmpty.setText("No results found for \"" + query + "\"");
                        } else {
                            adapter.setData(results);
                        }
                    } catch (Exception e) {
                        tvEmpty.setVisibility(View.VISIBLE);
                        tvEmpty.setText("Error parsing results");
                    }
                });
            }

            @Override
            public void onError(String message) {
                mainHandler.post(() -> {
                    progressBar.setVisibility(View.GONE);
                    if (isAdded()) {
                        Toast.makeText(requireContext(), message, Toast.LENGTH_SHORT).show();
                    }
                });
            }
        });
    }

    private void hideKeyboard() {
        if (getActivity() != null) {
            InputMethodManager imm = (InputMethodManager) requireActivity().getSystemService(android.content.Context.INPUT_METHOD_SERVICE);
            if (imm != null) imm.hideSoftInputFromWindow(etSearch.getWindowToken(), 0);
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
