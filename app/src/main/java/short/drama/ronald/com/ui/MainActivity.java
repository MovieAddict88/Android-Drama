package short.drama.ronald.com.ui;

import android.os.Bundle;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;

import com.google.android.material.bottomnavigation.BottomNavigationView;

import short.drama.ronald.com.R;
import short.drama.ronald.com.ui.home.HomeFragment;
import short.drama.ronald.com.ui.search.SearchFragment;

public class MainActivity extends AppCompatActivity {

    private HomeFragment homeFragment;
    private SearchFragment searchFragment;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        homeFragment = new HomeFragment();
        searchFragment = new SearchFragment();

        if (savedInstanceState == null) {
            loadFragment(homeFragment);
        }

        BottomNavigationView bottomNav = findViewById(R.id.bottom_nav);
        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                loadFragment(homeFragment);
                return true;
            } else if (id == R.id.nav_search) {
                loadFragment(searchFragment);
                return true;
            }
            return false;
        });
    }

    private void loadFragment(Fragment fragment) {
        getSupportFragmentManager()
                .beginTransaction()
                .replace(R.id.fragment_container, fragment)
                .commit();
    }
}
