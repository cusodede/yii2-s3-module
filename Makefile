build:
	@cp -nfr .env.example .env
	@cp -nfr ./tests/.env.example ./tests/.env
	docker-compose -f tests/docker-compose.yml pull
	docker-compose -f tests/docker-compose.yml build --pull

# Force rebuild images (use after Dockerfile changes)
rebuild:
	@cp -nfr .env.example .env
	@cp -nfr ./tests/.env.example ./tests/.env
	docker-compose -f tests/docker-compose.yml build --no-cache

test: test81 test84
test81:
	docker-compose -f tests/docker-compose.yml build php81
	docker-compose -f tests/docker-compose.yml run php81 vendor/bin/codecept run -v --debug
	docker-compose -f tests/docker-compose.yml down

test84:
	docker-compose -f tests/docker-compose.yml build php84
	docker-compose -f tests/docker-compose.yml run php84 vendor/bin/codecept run -v --debug
	docker-compose -f tests/docker-compose.yml down

# Quick test targets - run tests without rebuilding images
quick-test: quick-test81 quick-test84
quick-test81:
	docker-compose -f tests/docker-compose.yml run php81 vendor/bin/codecept run -v --debug
	docker-compose -f tests/docker-compose.yml down

quick-test84:
	docker-compose -f tests/docker-compose.yml run php84 vendor/bin/codecept run -v --debug
	docker-compose -f tests/docker-compose.yml down

clean:
	docker-compose -f tests/docker-compose.yml down
	rm -rf tests/runtime/*
	rm -rf composer.lock
	rm -rf vendor/

clean-all: clean
	rm -rf tests/runtime/.composer*
