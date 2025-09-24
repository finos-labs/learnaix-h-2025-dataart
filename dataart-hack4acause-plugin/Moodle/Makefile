snow_conn_name = hackaton
snow_role= MOODLE_ROLE

set-db:
	snow sql -q "CREATE DATABASE IF NOT EXISTS MOODLE_APP;"	 --connection ${snow_conn_name} --role ${snow_role}
	snow sql -q "CREATE IMAGE REPOSITORY IF NOT EXISTS MOODLE_APP.PUBLIC.IMG;"	 --connection ${snow_conn_name} --role ${snow_role}
	snow sql -q "CREATE OR REPLACE NETWORK RULE MOODLE_APP.PUBLIC.MOODLE_ALLOW_ALL \
	TYPE = HOST_PORT \
	MODE = EGRESS \
	VALUE_LIST = ('0.0.0.0:80', '0.0.0.0:443'); \
	CREATE OR REPLACE EXTERNAL ACCESS INTEGRATION moodle_eai ALLOWED_NETWORK_RULES = ('MOODLE_APP.PUBLIC.MOODLE_ALLOW_ALL') enabled = true; \
	GRANT USAGE ON INTEGRATION moodle_eai TO ROLE MOODLE_ROLE;" \
	--connection ${snow_conn_name} --role ${snow_role}

repo-login:
	snow spcs image-registry login --connection ${snow_conn_name}

repo-login-token:
	$(eval repository_url := $(shell snow spcs image-repository url MOODLE_APP.PUBLIC.IMG --connection ${snow_conn_name}))
	snow spcs image-registry token \
    --connection ${snow_conn_name} \
    --format=JSON | \
    docker login ${repository_url} \
      --username 0sessiontoken \
      --password-stdin

cpool:
	snow spcs compute-pool create moodle_compute_pool --if-not-exists --family CPU_X64_M --min-nodes 1 --max-nodes 1 --auto-suspend-secs 300 --connection ${snow_conn_name} --role ${snow_role}

serv:
	snow spcs service create moodle_service --database MOODLE_APP --schema PUBLIC  --if-not-exists --compute-pool moodle_compute_pool --spec-path ./moodle.yaml --connection ${snow_conn_name} --role ${snow_role} --eai-name "moodle_eai"

serv_upgrade:
	snow spcs service upgrade moodle_service --database MOODLE_APP --schema PUBLIC --spec-path ./moodle.yaml --connection ${snow_conn_name} --role ${snow_role}
build:
	docker build --no-cache --platform linux/amd64 -t natwest-hackathon .
tag: build
	$(eval moodle_tag := bitnamilegacy/moodle:5.0)
	$(eval pg_tag := bitnami/postgresql:17.5.0)
	$(eval natwest_hackathon_tag := natwest-hackathon:latest)
	$(eval repository_url := $(shell snow spcs image-repository url MOODLE_APP.PUBLIC.IMG --connection ${snow_conn_name}))
	docker tag ${pg_tag} ${repository_url}/${pg_tag}
	docker tag ${moodle_tag} ${repository_url}/${moodle_tag}
	docker tag ${natwest_hackathon_tag} ${repository_url}/${natwest_hackathon_tag}
push:
	$(eval moodle_tag := bitnamilegacy/moodle:5.0)
	$(eval pg_tag := bitnami/postgresql:17.5.0)
	$(eval natwest_hackathon_tag := natwest-hackathon:latest)
	$(eval repository_url := $(shell snow spcs image-repository url MOODLE_APP.PUBLIC.IMG --connection ${snow_conn_name}))
	docker push ${repository_url}/${moodle_tag}
	docker push ${repository_url}/${pg_tag}
	docker push ${repository_url}/${natwest_hackathon_tag}
build_hackaton_only: build
	$(eval natwest_hackathon_tag := natwest-hackathon:latest)
	$(eval repository_url := $(shell snow spcs image-repository url MOODLE_APP.PUBLIC.IMG --connection ${snow_conn_name}))
	docker tag ${natwest_hackathon_tag} ${repository_url}/${natwest_hackathon_tag}
	docker push ${repository_url}/${natwest_hackathon_tag}

#all: set-db repo-login build tag push
all: repo-login build tag push
#####################
# Utilities
#####################
.PHONY: update-requirements
update-requirements:
	pip install -U pip pip-tools
	pip-compile -U requirements.in --no-emit-index-url
	pip-compile -U requirements-test.in --no-emit-index-url
.PHONY: build-fastapi
build-fastapi:
	docker buildx build \
	-t natwest-hackathon \
	--no-cache .
.PHONY: run-service
run-service:
	docker run -p 5001:5000 --dns 8.8.8.8 --name natwest-hackathon natwest-hackathon
.PHONY: stop-service
stop-service:
	docker stop natwest-hackathon && docker rm natwest-hackathon
