document.addEventListener('DOMContentLoaded', () => {
    const movieGrid = document.getElementById('movieGrid');
    const playerSection = document.getElementById('player-section');
    const player = document.getElementById('mainPlayer');
    const nowPlayingTitle = document.getElementById('nowPlayingTitle');
    const nowPlayingDesc = document.getElementById('nowPlayingDesc');
    const closePlayer = document.getElementById('closePlayer');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    // Configuration from instructions
    const BASE_URL = 'https://api.starsinemax.com/api';
    const DEFAULT_VIDEO = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

    // Mock Data (Fallback if API is unreachable)
    const mockMovies = [
        {
            id: 1,
            title: "Casting Call: Actors",
            description: "Join our upcoming production. We are looking for talented male actors for various roles.",
            thumbnail: "https://starsinemax.com/ads/audition/ads-actor.JPG",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 2,
            title: "Casting Call: Actresses",
            description: "Exciting opportunities for female leads and supporting characters in our new drama series.",
            thumbnail: "https://starsinemax.com/ads/audition/ads-actress.JPG",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 3,
            title: "Teen Star Auditions",
            description: "Are you the next big teen star? We are scouting for young talent to shine on the big screen.",
            thumbnail: "https://starsinemax.com/ads/audition/starmaxx-casting-call.png",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 4,
            title: "Charismatic Models Wanted",
            description: "Fashion, commercials, and more. We need charismatic faces to represent our brand.",
            thumbnail: "https://starsinemax.com/ads/audition/ads-starmaxx-charismatic.png",
            videoUrl: DEFAULT_VIDEO
        },
        {
            id: 5,
            title: "StarMaxx Originals",
            description: "Explore the latest original content produced exclusively by Star SineMax studios.",
            thumbnail: "https://starsinemax.com/assets/img/starmax-logo-black.png",
            videoUrl: DEFAULT_VIDEO
        }
    ];

    let currentMovies = [...mockMovies];

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
                    currentMovies = data.map(item => ({
                        id: item.id || Math.random(),
                        title: item.title || item.name || "Untitled",
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
        displayMovies(currentMovies);
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

    searchBtn.addEventListener('click', () => {
        const query = searchInput.value.toLowerCase().trim();
        const filtered = currentMovies.filter(m =>
            m.title.toLowerCase().includes(query) ||
            (m.description && m.description.toLowerCase().includes(query))
        );
        displayMovies(filtered);
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });

    // Run the application
    fetchMovies();
});
