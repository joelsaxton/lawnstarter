# Lawnstarter Star Wars App

I set up a React front end in Typescript, and a Laravel back using Sail

## API Note
For API documentation see `backend/API.md`. I wrote tests for the API endpoints as well as the Star Wars API stats job. See the tests folder.

## Quick Start

### 1. Clone the repository
```bash
git clone https://github.com/joelsaxton/lawnstarter.git lawnstarter
```
OR 
```
git clone git@github.com:joelsaxton/lawnstarter.git lawnstarter
```
then
```
cd lawnstarter
````

### 2. Run the setup script
```bash
./setup.sh
```

This script will:
- Copy `.env.example` to `.env` and set user/group IDs
- Copy `backend/.env.example` to `backend/.env`
- Install Laravel dependencies using Docker

### 3. Configure Sail alias (one-time setup)

Add the Sail alias to your shell configuration:
```bash
echo "alias sail='sh \$([ -f sail ] && echo sail || echo backend/vendor/bin/sail)'" >> ~/.zshrc
source ~/.zshrc
```

> **Note**: Use `~/.bashrc`, `~/.zprofile`, or wherever your specific file is located.

### 4. Start the application
```bash
sail build
sail up -d
```

This will start the following containers:
- **laravel.test**: Main Laravel application server
- **scheduler**: Runs the Laravel scheduler (checks every minute for scheduled tasks)
- **queue**: Processes queued jobs from Redis
- **mysql**: Database server
- **redis**: Cache and queue storage
- **frontend**: React development server

### 5. Run migrations (first time only)
```bash
sail artisan migrate
```

## Background Services

The application includes several background services:

### Scheduler
Runs Laravel's task scheduler which executes scheduled commands. Currently configured tasks:
- **Star Wars API Stats**: Calculates API usage statistics every 5 minutes

### Queue Worker
Processes the Star Wars API statistics calculation background job from the Redis queue.

## Access

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost/api
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Development Commands

### Testing the Back End
```bash
# Run tests
sail test
```

> **Note**: If you want to test the Star Wars endpoints directly, I recommend running Postman as a local application. Make sure the `Accept` and `Content-Type` headers are both `application/json`

### Docker
```bash
# Start containers
sail up -d

# Stop containers
sail down

# View logs for all services
sail logs

# Restart a specific service
docker compose restart queue
docker compose restart scheduler
```

## Stopping the application
```bash
sail down
```

## Architecture Notes

### Queue System
- Uses Redis for queuing
- Default queue worker config, e.g. 3 retries

### Cache System
- Uses Redis for caching
- Star Wars API stats are cached and regenerated every 5 minutes
- Cache key: `star_wars_api_stats`

### Scheduler
- Runs `php artisan schedule:work`
- Runs Star Wars API stats update every 5 minutes