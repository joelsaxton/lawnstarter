import {useEffect, useState} from 'react';
import {useParams, Link, useNavigate} from 'react-router-dom';
import {
    Container,
    Typography,
    Box,
    Paper,
    List,
    ListItem,
    ListItemButton,
    ListItemText,
    CircularProgress,
    Alert,
    Button,
    Divider,
} from '@mui/material';
import apiClient from '../api/client';
import type {Film} from '../types/Film';

const MovieDetail = () => {
    const {id} = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [film, setFilm] = useState<Film | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchFilm = async () => {
            try {
                setLoading(true);
                setError(null);
                const response = await apiClient.get(`/starwars/film/${id}`);
                setFilm(response.data);
            } catch (err) {
                setError('Failed to load film details');
                console.error(err);
            } finally {
                setLoading(false);
            }
        };

        if (id) {
            fetchFilm();
        }
    }, [id]);

    if (loading) {
        return (
            <Container maxWidth="lg"
                       sx={{display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '50vh'}}>
                <CircularProgress/>
            </Container>
        );
    }

    if (error || !film) {
        return (
            <Container maxWidth="lg"
                       sx={{display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '50vh'}}>
                <Alert severity="error">{error || 'Film not found'}</Alert>
            </Container>
        );
    }

    return (
        <Container maxWidth="lg" sx={{py: 4}}>
            <Box sx={{display: 'flex', flexDirection: 'column', alignItems: 'center', width: '100%'}}>
                {/* Single Box with Two Columns using Flexbox */}
                <Paper
                    sx={{
                        p: 3,
                        bgcolor: 'rgba(255, 255, 255, 0.95)',
                        border: '1px solid #ccc',
                        borderRadius: 2,
                        maxWidth: '1200px',
                        width: '100%',
                    }}
                >
                    <Box sx={{display: 'flex', gap: 4, flexDirection: {xs: 'column', md: 'row'}}}>
                        {/* Left Column - Film Info */}
                        <Box sx={{flex: 1}}>
                            <Typography variant="h6" gutterBottom sx={{color: '#000', fontWeight: 'bold'}}>
                                {film.title}
                            </Typography>

                            <Typography variant="h5" gutterBottom sx={{color: '#000', mt: 2}}>
                                Opening Crawl
                            </Typography>
                            <Divider sx={{mb: 2}}/>

                            <Box sx={{mb: 3}}>
                                <Typography
                                    variant="body1"
                                    sx={{
                                        color: '#000',
                                        whiteSpace: 'pre-line',
                                        lineHeight: 1.8,
                                    }}
                                >
                                    {film.opening_crawl}
                                </Typography>
                            </Box>

                            {/* Back to Search Button */}
                            <Button
                                fullWidth
                                variant="contained"
                                onClick={() => navigate('/')}
                                sx={{
                                    bgcolor: '#4caf50',
                                    color: '#fff',
                                    '&:hover': {bgcolor: '#45a049'},
                                    borderRadius: 8,
                                    py: 1.5,
                                    '&:focus': { outline: 'none' },
                                    '&:focus-visible': { outline: '2px solid #45a049', outlineOffset: '2px' },
                                }}
                            >
                                Back to Search
                            </Button>
                        </Box>

                        {/* Right Column - Characters */}
                        <Box sx={{flex: 1}}>
                            {/* Invisible spacer to match the h6 height on the left */}
                            <Box sx={{height: '32px', mb: '0.35em'}}/>

                            <Typography variant="h5" gutterBottom sx={{color: '#000', mt: 2}}>
                                Characters
                            </Typography>
                            <Divider sx={{mb: 2}}/>

                            {film.characters && film.characters.length > 0 ? (
                                <Typography variant="body1" sx={{color: '#000'}}>
                                    {film.characters.map((character, index) => (
                                        <span key={character.id}>
          <Link
              to={`/person/${character.id}`}
              style={{
                  color: '#2196f3',
                  textDecoration: 'none',
              }}
              onMouseEnter={(e) => {
                  e.currentTarget.style.textDecoration = 'underline';
              }}
              onMouseLeave={(e) => {
                  e.currentTarget.style.textDecoration = 'none';
              }}
          >
            {character.name}
          </Link>
                                            {index < film.characters.length - 1 && ', '}
        </span>
                                    ))}
                                </Typography>
                            ) : (
                                <Typography variant="body2" sx={{color: '#666'}}>
                                    No characters found
                                </Typography>
                            )}
                        </Box>
                    </Box>
                </Paper>
            </Box>
        </Container>
    );
};

export default MovieDetail;