PM_VERSION=4.6.0

build_base:
	docker build -t pm4-base:local -f Dockerfile.base .

build_app:
	docker build --build-arg PM_VERSION=$(PM_VERSION) -t processmaker/pm4-core:local .

clean:
	docker rmi pm4-base:local
	docker rmi processmaker/pm4-core:local
