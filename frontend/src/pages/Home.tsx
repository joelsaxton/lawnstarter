import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Container,
    Typography,
    Box,
    Paper,
    Grid,
    Radio,
    RadioGroup,
    FormControlLabel,
    FormControl,
    TextField,
    Button,
    List,
    ListItem,
} from '@mui/material';
import apiClient from '../api/client';

type SearchType = 'person' | 'movie';

type SearchResult = {
    uid: string;
    name?: string;
    title?: string;
};

const Home = () => {
    const navigate = useNavigate();
    const [searchType, setSearchType] = useState<SearchType>('person');
    const [searchQuery, setSearchQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [hasSearched, setHasSearched] = useState(false);

    const performSearch = async () => {
        if (!searchQuery.trim()) return;

        setLoading(true);
        setHasSearched(true);

        try {
            const endpoint =
                searchType === 'person'
                    ? `/starwars/person?name=${encodeURIComponent(searchQuery)}`
                    : `/starwars/film?title=${encodeURIComponent(searchQuery)}`;

            const response = await apiClient.get(endpoint);
            setResults(response.data || []);

            // Change background on successful search with results
            if (response.data && response.data.length > 0) {
                window.dispatchEvent(new Event('changeBackground'));
            }
        } catch (error) {
            console.error('Search error:', error);
            setResults([]);
        } finally {
            setLoading(false);
        }
    };

    const handleSearchClick = () => {
        performSearch();
    };

    const handleSeeDetails = (id: string) => {
        const route = searchType === 'person' ? `/person/${id}` : `/movie/${id}`;
        navigate(route);
    };

    const getDisplayName = (result: SearchResult) => {
        return result.name || result.title || 'Unknown';
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            performSearch();
        }
    };

    return (
        <Container maxWidth="lg" sx={{ py: 4 }}>
            <Box sx={{ display: 'flex', gap: 3, flexDirection: { xs: 'column', md: 'row' } }}>
                {/* Left Pane - Search Form (1 unit) */}
                <Box sx={{ flex: { xs: '1 1 100%', md: '1 1 33%' } }}>
                    <Paper
                        sx={{
                            p: 3,
                            bgcolor: 'rgba(255, 255, 255, 0.95)',
                            border: '1px solid #ccc',
                            borderRadius: 1,
                        }}
                    >
                        <Typography variant="h5" gutterBottom sx={{ color: '#000' }}>
                            What are you searching for?
                        </Typography>

                        <FormControl component="fieldset" sx={{ mt: 2, mb: 2 }}>
                            <RadioGroup
                                row
                                value={searchType}
                                onChange={(e) => setSearchType(e.target.value as SearchType)}
                            >
                                <FormControlLabel
                                    value="person"
                                    control={<Radio />}
                                    label="People"
                                    sx={{ color: '#000' }}
                                />
                                <FormControlLabel
                                    value="movie"
                                    control={<Radio />}
                                    label="Movies"
                                    sx={{ color: '#000' }}
                                />
                            </RadioGroup>
                        </FormControl>

                        <TextField
                            fullWidth
                            label={`Search ${searchType === 'person' ? 'Person' : 'Movie'}`}
                            variant="outlined"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            onKeyPress={handleKeyPress}
                            sx={{ mb: 2 }}
                        />

                        <Button
                            fullWidth
                            variant="contained"
                            onClick={handleSearchClick}
                            sx={{
                                bgcolor: '#4caf50',
                                color: '#fff',
                                '&:hover': { bgcolor: '#45a049' },
                                borderRadius: 4,
                                '&:focus': { outline: 'none' },
                                '&:focus-visible': { outline: '2px solid #45a049', outlineOffset: '2px' },
                            }}
                        >
                            Search
                        </Button>
                    </Paper>
                </Box>

                {/* Right Pane - Results (2 units, twice as wide) */}
                <Box sx={{ flex: { xs: '1 1 100%', md: '2 1 66%' } }}>
                    <Paper
                        sx={{
                            p: 3,
                            bgcolor: 'rgba(255, 255, 255, 0.95)',
                            border: '1px solid #ccc',
                            borderRadius: 1,
                            minHeight: 400,
                        }}
                    >
                        <Typography variant="h5" gutterBottom sx={{ color: '#000' }}>
                            Results
                        </Typography>

                        {loading ? (
                            <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
                                <Typography sx={{ color: '#000' }}>Searching...</Typography>
                            </Box>
                        ) : !hasSearched || results.length === 0 ? (
                            <Box
                                sx={{
                                    display: 'flex',
                                    justifyContent: 'center',
                                    alignItems: 'center',
                                    minHeight: 450
                                }}
                            >
                                <Typography sx={{ color: '#999', mt: 2, textAlign: 'center', fontWeight: 600 }}>
                                    There are zero matches. Use the form to search for People or Movies.
                                </Typography>
                            </Box>
                        ) : (
                            <List>
                                {results.map((result) => (
                                    <ListItem
                                        key={result.uid}
                                        sx={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            borderBottom: '1px solid #aaa',
                                            py: 2,
                                        }}
                                    >
                                        <Typography sx={{ color: '#000' }}>
                                            {getDisplayName(result)}
                                        </Typography>
                                        <Button
                                            variant="contained"
                                            size="small"
                                            onClick={() => handleSeeDetails(result.uid)}
                                            sx={{
                                                bgcolor: '#4caf50',
                                                color: '#fff',
                                                '&:hover': { bgcolor: '#45a049' },
                                                borderRadius: 4,
                                                marginLeft: 2,
                                                '&:focus': { outline: 'none' },
                                                '&:focus-visible': { outline: '2px solid #45a049', outlineOffset: '2px' },
                                            }}
                                        >
                                            See Details
                                        </Button>
                                    </ListItem>
                                ))}
                            </List>
                        )}
                    </Paper>
                </Box>
            </Box>
        </Container>
    );
};

export default Home;