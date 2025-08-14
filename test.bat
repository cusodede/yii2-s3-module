@echo off
REM Windows batch script for running tests without make

echo Running tests for PHP 8.1 and PHP 8.4...

REM Setup environment files if they don't exist
if not exist tests\.env (
    copy tests\.env.example tests\.env
)
if not exist .env (
    copy .env.example .env
)

REM Run tests for both PHP versions
call :test81
call :test84
goto :end

:test81
echo.
echo Building and testing PHP 8.1...
docker compose -f tests/docker-compose.yml build php81
docker compose -f tests/docker-compose.yml run php81 vendor/bin/codecept run -v --debug
docker compose -f tests/docker-compose.yml down
goto :eof

:test84
echo.
echo Building and testing PHP 8.4...
docker compose -f tests/docker-compose.yml build php84
docker compose -f tests/docker-compose.yml run php84 vendor/bin/codecept run -v --debug
docker compose -f tests/docker-compose.yml down
goto :eof

:end
echo.
echo All tests completed!