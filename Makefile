.PHONY: test-8.3 test-8.4 test-8.5 test-all

test-8.3:
	docker compose run --rm test-8.3

test-8.4:
	docker compose run --rm test-8.4

test-8.5:
	docker compose run --rm test-8.5

test-all: test-8.3 test-8.4 test-8.5
