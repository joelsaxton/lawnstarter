import { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material';
import Home from './pages/Home';
import PersonDetail from './pages/PersonDetail';
import MovieDetail from './pages/MovieDetail';

const theme = createTheme({
    palette: {
        mode: 'light',
    },
});

// Rotating background images based on successful search
const backgroundImages = [
    'https://media.cnn.com/api/v1/images/stellar/prod/171207165454-star-wars-architecture-death-star-rogue-one.jpg',
    'https://www.syfy.com/sites/syfy/files/starwars_tattooine_binary_sunset.jpg',
    'https://lumiere-a.akamaihd.net/v1/images/Star-Destroyer_ab6b94bb.jpeg',
    'https://cdn.mos.cms.futurecdn.net/uciG9WygFRtEDcvw9gitTd.jpg',
    'https://www.thedigitalfix.com/wp-content/sites/thedigitalfix/2023/11/Untitled-design.jpg',
    'https://lumiere-a.akamaihd.net/v1/images/Hoth_d074d307.jpeg',
    'https://lumiere-a.akamaihd.net/v1/images/image_1a900f65.jpeg'
];

function AppContent() {
    const location = useLocation();
    const [backgroundIndex, setBackgroundIndex] = useState(0);

    // Listen for background change events
    useEffect(() => {
        const handleBackgroundChange = () => {
            setBackgroundIndex((prev) => (prev + 1) % backgroundImages.length);
        };

        window.addEventListener('changeBackground', handleBackgroundChange);
        return () => window.removeEventListener('changeBackground', handleBackgroundChange);
    }, []);

    // Apply background to body
    useEffect(() => {
        document.body.style.backgroundImage = `url('${backgroundImages[backgroundIndex]}')`;
    }, [backgroundIndex]);

    return (
        <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/person/:id" element={<PersonDetail />} />
            <Route path="/movie/:id" element={<MovieDetail />} />
        </Routes>
    );
}

function App() {
    return (
        <ThemeProvider theme={theme}>
            <Router>
                <AppContent />
            </Router>
        </ThemeProvider>
    );
}

export default App;