.DEFAULT_GOAL := help

# Определение ID пользователя и группы для хост-машины (кроме MacOS)
ifeq ($(shell uname), Darwin)
    export UID=1000
    export GID=1000
else
    export UID=$(shell id -u)
    export GID=$(shell id -g)
endif

DOCKER_COMPOSE := docker compose -f docker/compose.yml --env-file docker/.env

init: build up db-clean-import ## Полная инициализация проекта с нуля (сборка, запуск, очистка базы)

build: ## Сборка docker-образов 
	$(DOCKER_COMPOSE) build

no-cache: ## Сборка docker-образов без использования кэша
	$(DOCKER_COMPOSE) build --no-cache

up: ## Запуск окружения разработки в фоновом режиме
	$(DOCKER_COMPOSE) up -d

down: ## Остановка контейнеров и удаление сопутствующих ресурсов
	$(DOCKER_COMPOSE) down --remove-orphans

clear: ## Полное удаление контейнеров, сетей и сохраненных данных базы данных
	$(DOCKER_COMPOSE) down --volumes --remove-orphans
	rm -rf docker-data/

shell: ## Вход в консоль контейнера PHP от имени пользователя
	$(DOCKER_COMPOSE) exec -u $(UID):$(GID) php bash

db-clean-import: ## Сброс базы данных PostgreSQL и повторное создание таблиц с индексами
	$(DOCKER_COMPOSE) down --volumes --remove-orphans
	rm -rf docker-data/
	$(DOCKER_COMPOSE) up -d database
	@echo "Ожидание инициализации PostgreSQL..."
	@sleep 3
	$(DOCKER_COMPOSE) up -d

# Скрипт автоматического документирования команд на основе комментариев '##'
help: ## Отображение списка всех доступных команд и их описание
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
