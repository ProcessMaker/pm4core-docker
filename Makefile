VERSION = 4.1.21
PLATFORMS = "linux/amd64,linux/arm64"
DOCKER_REPO = "processmaker4/pm4-core"

build:
	docker buildx build --platform $(PLATFORMS) --build-arg PM_VERSION=$(VERSION) -t $(DOCKER_REPO):ci -f Dockerfile.new .

build-compose:
	docker compose build --build-arg PM_VERSION=$(VERSION)
