@echo off
REM Windows batch script for cleaning up test environment

echo Cleaning up test environment...

REM Stop and remove containers
docker-compose -f tests/docker-compose.yml down

REM Clean test runtime files
if exist tests\runtime (
    echo Removing test runtime files...
    rmdir /s /q tests\runtime
    mkdir tests\runtime
)

REM Remove composer.lock and vendor if requested
set /p cleanall="Remove composer.lock and vendor folder? (y/n): "
if /i "%cleanall%"=="y" (
    if exist composer.lock del composer.lock
    if exist vendor rmdir /s /q vendor
    echo Full cleanup completed!
) else (
    echo Basic cleanup completed!
)