document.addEventListener('DOMContentLoaded', () => {
    const movieGrid = document.getElementById('movieGrid');
    const playerSection = document.getElementById('player-section');
    const player = document.getElementById('mainPlayer');
    const nowPlayingTitle = document.getElementById('nowPlayingTitle');
    const nowPlayingDesc = document.getElementById('nowPlayingDesc');
    const closePlayer = document.getElementById('closePlayer');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const navLinks = document.querySelectorAll('nav a');
    const sectionTitle = document.querySelector('#trending h2');

    // Configuration from instructions
    const BASE_URL = 'https://api.starsinemax.com/api';
    const DEFAULT_VIDEO = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

    // Mock Data (Fallback if API is unreachable)
    const mockMovies = [
        {
            id: 1,
            title: "Casting Call: Actors",
            category: "Movie",
            description: "Join our upcoming production. We are looking for talented male actors for various roles.",
            thumbnail: "https://starsinemax.com/ads/audition/ads-actor.JPG",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 2,
            title: "Casting Call: Actresses",
            category: "Movie",
            description: "Exciting opportunities for female leads and supporting characters in our new drama series.",
            thumbnail: "https://starsinemax.com/ads/audition/ads-actress.JPG",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 3,
            title: "Teen Star Auditions",
            category: "TV Show",
            description: "Are you the next big teen star? We are scouting for young talent to shine on the big screen.",
            thumbnail: "https://starsinemax.com/ads/audition/starmaxx-casting-call.png",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 4,
            title: "Charismatic Models Wanted",
            category: "Movie",
            description: "Fashion, commercials, and more. We need charismatic faces to represent our brand.",
            thumbnail: "https://starsinemax.com/ads/audition/ads-starmaxx-charismatic.png",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 5,
            title: "StarMaxx Originals",
            category: "TV Show",
            description: "Explore the latest original content produced exclusively by Star SineMax studios.",
            thumbnail: "https://starsinemax.com/assets/img/starmax-logo-black.png",
            videoUrl: DEFAULT_VIDEO
        }
    ];

    let allMovies = [...mockMovies];
    let currentFilter = 'All';

    /**
     * Attempts to fetch movies from the StarMaxx API.
     * Falls back to mock data if the API is restricted (403/404) or encounters CORS issues.
     */
    async function fetchMovies() {
        console.log(`Fetching movies from ${BASE_URL}...`);
        try {
            // Attempting to fetch from common API endpoints
            const response = await fetch(`${BASE_URL}/movies`, {
                method: 'GET',
                mode: 'cors', // Note: This will likely fail due to CORS on a private API
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (Array.isArray(data) && data.length > 0) {
                    allMovies = data.map(item => ({
                        id: item.id || Math.random(),
                        title: item.title || item.name || "Untitled",
                        category: item.category || (Math.random() > 0.5 ? "Movie" : "TV Show"),
                        description: item.description || item.plot || "No description available.",
                        thumbnail: item.thumbnail || item.poster || "https://starsinemax.com/assets/img/starmax-logo-black.png",
                        videoUrl: item.videoUrl || item.url || DEFAULT_VIDEO
                    }));
                }
            } else {
                console.warn(`API returned status ${response.status}. Using high-quality mock data.`);
            }
        } catch (error) {
            console.error("API Fetch Error (likely CORS or Offline):", error.message);
            console.log("Falling back to StarMaxx production assets.");
        }
        applyFilter();
    }

    /**
     * Safely renders movie cards to the grid.
     */
    function displayMovies(movies) {
        movieGrid.innerHTML = '';
        if (movies.length === 0) {
            const noResults = document.createElement('p');
            noResults.classList.add('no-results');
            noResults.textContent = 'No movies found matching your search.';
            movieGrid.appendChild(noResults);
            return;
        }

        movies.forEach(movie => {
            const card = document.createElement('div');
            card.classList.add('movie-card');

            const img = document.createElement('img');
            img.src = movie.thumbnail;
            img.alt = movie.title;
            img.onerror = () => { img.src = 'https://starsinemax.com/assets/img/starmax-logo-black.png'; };

            const h3 = document.createElement('h3');
            h3.textContent = movie.title;

            card.appendChild(img);
            card.appendChild(h3);

            card.addEventListener('click', () => playMovie(movie));
            movieGrid.appendChild(card);
        });
    }

    function playMovie(movie) {
        player.src = movie.videoUrl;
        nowPlayingTitle.textContent = movie.title;
        nowPlayingDesc.textContent = movie.description;
        playerSection.classList.remove('hidden');
        playerSection.scrollIntoView({ behavior: 'smooth' });

        // Ensure the video plays
        player.load();
        player.play().catch(error => {
            console.log("Auto-play blocked by browser. User interaction required.");
        });
    }

    closePlayer.addEventListener('click', () => {
        player.pause();
        player.src = "";
        playerSection.classList.add('hidden');
    });

    function applyFilter() {
        let filtered = allMovies;

        if (currentFilter !== 'All') {
            filtered = allMovies.filter(m => m.category === currentFilter);
            sectionTitle.textContent = currentFilter === 'Movie' ? 'Movies' : 'TV Shows';
        } else {
            sectionTitle.textContent = 'Trending Now';
        }

        const query = searchInput.value.toLowerCase().trim();
        if (query) {
            filtered = filtered.filter(m =>
                m.title.toLowerCase().includes(query) ||
                (m.description && m.description.toLowerCase().includes(query))
            );
        }

        displayMovies(filtered);
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();

            // UI Update
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Logic Update
            const id = link.id;
            if (id === 'nav-home') currentFilter = 'All';
            else if (id === 'nav-movies') currentFilter = 'Movie';
            else if (id === 'nav-tv') currentFilter = 'TV Show';

            applyFilter();
        });
    });

    searchBtn.addEventListener('click', () => {
        applyFilter();
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });

    // Run the application
    fetchMovies();
});
