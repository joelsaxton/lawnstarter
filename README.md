# Lawnstarter

A monorepo containing a React TypeScript frontend and Laravel API backend.

## Prerequisites

- Docker & Docker Compose
- Node.js 20+ (for local frontend development)
- PHP 8.3+ & Composer (for local backend development)

## Quick Start

### 1. Clone the repository
```bash
git clone https://github.com/joelsaxton/lawnstarter.git lawnstarter
```
OR 
```
git clone git@github.com:joelsaxton/lawnstarter.git
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
Processes background jobs from the Redis queue, including:
- Star Wars API statistics calculation
- Other async tasks

You can monitor the queue worker logs:
```bash
sail logs -f queue
```

## Access

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost/api
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Development Commands

### Backend (Laravel)
```bash
# Run artisan commands
sail artisan <command>

# Run composer
sail composer <command>

# Run tests
sail artisan test

# Access the container shell
sail shell

# Run Tinker
sail tinker

# Manually trigger stats calculation
sail artisan stats:calculate-star-wars-api

# Monitor queue jobs
sail artisan queue:monitor

# Clear cache
sail artisan cache:clear
```

> **Note**: If you want to test the Star Wars endpoint directly, I recommend running Postman as a local application. Make sure the `Accept` and `Content-Type` headers are both `application/json`

### Frontend (React)
```bash
# Access frontend container
docker compose exec frontend sh

# Or run npm commands directly
docker compose exec frontend npm <command>
```

### Redis
```bash
# Access Redis CLI
sail redis-cli

# Check cached stats
sail redis-cli GET laravel_database_star_wars_api_stats

# Monitor Redis commands in real-time
sail redis-cli MONITOR
```

### Docker
```bash
# Start containers
sail up -d

# Stop containers
sail down

# View logs for all services
sail logs

# View logs for specific service
sail logs -f queue
sail logs -f scheduler
sail logs -f laravel.test

# Rebuild containers
sail build --no-cache

# Restart a specific service
docker compose restart queue
docker compose restart scheduler
```

## Troubleshooting

### Queue not processing jobs
```bash
# Check queue worker logs
sail logs -f queue

# Restart the queue worker
docker compose restart queue
```

### Scheduler not running
```bash
# Check scheduler logs
sail logs -f scheduler

# Restart the scheduler
docker compose restart scheduler
```

### Redis connection issues
```bash
# Check Redis is running
sail redis-cli ping
# Should return: PONG

# Check Redis stats
sail redis-cli INFO
```

### Clear all caches and restart
```bash
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear
docker compose restart
```

## Stopping the application
```bash
sail down

# Stop and remove volumes (WARNING: deletes database data)
sail down -v
```

## Architecture Notes

### Queue System
- Uses Redis for fast, reliable job queuing
- Queue worker processes jobs with 3 retry attempts
- 90-second timeout per job

### Cache System
- Uses Redis for high-performance caching
- Stats are cached and regenerated every 5 minutes
- Cache key: `star_wars_api_stats`

### Scheduler
- Runs `php artisan schedule:work` continuously
- Checks for scheduled tasks every minute
- Currently runs stats calculation every 5 minutes with overlap prevention