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
import type {Person} from '../types/Person';

const PersonDetail = () => {
    const {id} = useParams<{ id: string }>();
    const navigate = useNavigate();
    const [person, setPerson] = useState<Person | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchPerson = async () => {
            try {
                setLoading(true);
                setError(null);
                const response = await apiClient.get(`/starwars/person/${id}`);
                setPerson(response.data);
            } catch (err) {
                setError('Failed to load person details');
                console.error(err);
            } finally {
                setLoading(false);
            }
        };

        if (id) {
            fetchPerson();
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

    if (error || !person) {
        return (
            <Container maxWidth="lg"
                       sx={{display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '50vh'}}>
                <Alert severity="error">{error || 'Person not found'}</Alert>
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
                        {/* Left Column - Details */}
                        <Box sx={{flex: 1}}>
                            <Typography variant="h6" gutterBottom sx={{color: '#000', fontWeight: 'bold'}}>
                                {person.name}
                            </Typography>

                            <Typography variant="h5" gutterBottom sx={{color: '#000', mt: 2}}>
                                Details
                            </Typography>
                            <Divider sx={{mb: 2}}/>

                            <Box sx={{mb: 3}}>
                                <DetailRow label="Birth Year" value={person.birth_year}/>
                                <DetailRow label="Gender" value={person.gender}/>
                                <DetailRow label="Eye Color" value={person.eye_color}/>
                                <DetailRow label="Hair Color" value={person.hair_color}/>
                                <DetailRow label="Height" value={`${person.height} cm`}/>
                                <DetailRow label="Mass" value={`${person.mass} kg`}/>
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

                        {/* Right Column - Movies */}
                        <Box sx={{flex: 1}}>
                            {/* Invisible spacer to match the h6 height on the left */}
                            <Box sx={{height: '32px', mb: '0.35em'}}/>

                            <Typography variant="h5" gutterBottom sx={{color: '#000', mt: 2}}>
                                Movies
                            </Typography>
                            <Divider sx={{mb: 2}}/>

                            {person.movies && person.movies.length > 0 ? (
                                <Typography variant="body1" sx={{color: '#000'}}>
                                    {person.movies.map((movie, index) => (
                                        <span key={movie.id}>
          <Link
              to={`/movie/${movie.id}`}
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
            {movie.title}
          </Link>
                                            {index < person.movies.length - 1 && ', '}
        </span>
                                    ))}
                                </Typography>
                            ) : (
                                <Typography variant="body2" sx={{color: '#666'}}>
                                    No movies found
                                </Typography>
                            )}
                        </Box>
                    </Box>
                </Paper>
            </Box>
        </Container>
    );
};

// Helper component for detail rows
type DetailRowProps = {
    label: string;
    value: string;
};

const DetailRow = ({label, value}: DetailRowProps) => (
    <Box sx={{display: 'flex', mb: 1.5}}>
        <Typography variant="body1" sx={{fontWeight: 'bold', minWidth: 120, color: '#000'}}>
            {label}:
        </Typography>
        <Typography variant="body1" sx={{textTransform: 'capitalize', color: '#000'}}>
            {value}
        </Typography>
    </Box>
);

export default PersonDetail;