#!/bin/bash
set -e

echo "Starting PostgreSQL database initialization..."

# Function to extract database name from DSN
extract_db_name() {
    local dsn="$1"
    echo "$dsn" | sed -n 's/.*dbname=\([^;]*\).*/\1/p'
}

# Function to extract database user from env file
extract_db_user() {
    local env_file="$1"
    grep "^DB_USER=" "$env_file" | cut -d'=' -f2 | tr -d '"'
}

# Function to extract database password from env file
extract_db_pass() {
    local env_file="$1"
    grep "^DB_PASS=" "$env_file" | cut -d'=' -f2 | tr -d '"'
}

# Function to extract DSN from env file
extract_dsn() {
    local env_file="$1"
    grep "^DB_DSN=" "$env_file" | cut -d'=' -f2- | tr -d '"'
}

# Function to create database and user
create_database() {
    local env_file="$1"
    local env_name="$2"
    
    if [[ -f "$env_file" ]]; then
        echo "Processing $env_name environment from $env_file"
        
        local dsn=$(extract_dsn "$env_file")
        local db_name=$(extract_db_name "$dsn")
        local db_user=$(extract_db_user "$env_file")
        local db_pass=$(extract_db_pass "$env_file")
        
        if [[ -n "$db_name" ]]; then
            echo "Creating database: $db_name"
            psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
                -- Create database if it doesn't exist
                SELECT 'CREATE DATABASE $db_name'
                WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$db_name');
                \gexec
                
                -- Create user if it doesn't exist (only if different from postgres)
                DO \$\$
                BEGIN
                    IF '$db_user' != '$POSTGRES_USER' THEN
                        IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '$db_user') THEN
                            CREATE USER $db_user WITH PASSWORD '$db_pass';
                        END IF;
                        GRANT ALL PRIVILEGES ON DATABASE $db_name TO $db_user;
                        ALTER DATABASE $db_name OWNER TO $db_user;
                    END IF;
                END
                \$\$;
EOSQL
            echo "Database $db_name created successfully for $env_name environment"
        else
            echo "Warning: Could not extract database name from DSN in $env_file"
        fi
    else
        echo "Warning: Environment file $env_file not found"
    fi
}

# Create databases for development and test environments
create_database "/tmp/.env.dev" "DEVELOPMENT"
create_database "/tmp/.env.test" "TEST"

echo "PostgreSQL initialization completed successfully!"