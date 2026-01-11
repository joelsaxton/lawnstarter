export type Movie = {
    id: number;
    title: string;
};

export type Person = {
    uid: string;
    name: string;
    birth_year: string;
    gender: string;
    eye_color: string;
    hair_color: string;
    height: string;
    mass: string;
    movies: Movie[];
};