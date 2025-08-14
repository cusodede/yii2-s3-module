@echo off
REM Windows batch script for running PHP 8.4 tests only

echo Running tests for PHP 8.4...

REM Setup environment files if they don't exist
if not exist tests\.env (
    copy tests\.env.example tests\.env
)
if not exist .env (
    copy .env.example .env
)

echo Building and testing PHP 8.4...
docker-compose -f tests/docker-compose.yml build php84
docker-compose -f tests/docker-compose.yml run php84 vendor/bin/codecept run -v --debug
docker-compose -f tests/docker-compose.yml down

echo PHP 8.4 tests completed!