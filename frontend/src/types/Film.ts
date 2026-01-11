export type Character = {
    id: number;
    name: string;
};

export type Film = {
    uid: string;
    title: string;
    opening_crawl: string;
    director: string;
    producer: string;
    release_date: string;
    episode_id: number;
    characters: Character[];
};