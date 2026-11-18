# Makefile deploy production
# Pemakaian utama:  make deploy

SHELL        := /bin/bash
.SHELLFLAGS  := -o pipefail -c

DC           := docker compose -f compose.yaml -f compose.prod.yaml
PHP_SERVICES := app queue scheduler reverb
BRANCH       ?= main
BACKUP_DIR   := storage/app/backups
KEEP         ?= 10
APP_RUN      := $(DC) run --rm --no-deps -T app

.DEFAULT_GOAL := help
.PHONY: help deploy pull build db-up migrate up optimize status logs shell \
        migrate-status rollback down prune backup backups restore

help: ## Tampilkan daftar perintah
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

deploy: pull build db-up backup migrate up optimize status ## Update penuh: pull, build, migrasi, restart
	@echo "Deploy selesai"

pull: ## Ambil kode terbaru dari git
	git pull --ff-only origin $(BRANCH)

build: ## Build image app & nginx (hanya 2 service yang punya build)
	$(DC) build app nginx

db-up: ## Pastikan MySQL jalan dan siap
	$(DC) up -d --wait mysql

backup: ## Backup DB ke storage/app/backups (di volume app-storage)
	@name=db-$$(date +%Y%m%d-%H%M%S).sql.gz; \
	$(DC) exec -T mysql sh -c 'exec mysqldump --single-transaction --routines --triggers \
		-uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"' \
	| gzip \
	| $(APP_RUN) sh -c "mkdir -p $(BACKUP_DIR) && cat > $(BACKUP_DIR)/$$name \
		&& ls -t $(BACKUP_DIR)/db-*.sql.gz | tail -n +$$(($(KEEP)+1)) | xargs -r rm -f" \
	&& echo "Backup tersimpan: $(BACKUP_DIR)/$$name"

backups: ## Lihat daftar backup
	$(APP_RUN) sh -c 'ls -lh $(BACKUP_DIR)/db-*.sql.gz 2>/dev/null || echo "Belum ada backup"'

restore: ## Restore DB (contoh: make restore f=db-20261118-120000.sql.gz)
	@test -n "$(f)" || (echo "Sebutkan file: make restore f=db-xxx.sql.gz (lihat: make backups)" && exit 1)
	@$(APP_RUN) test -f $(BACKUP_DIR)/$(f) || (echo "$(BACKUP_DIR)/$(f) tidak ditemukan" && exit 1)
	@read -p "Database akan ditimpa dengan $(f). Lanjut? [y/N] " ans && [ "$$ans" = "y" ]
	$(APP_RUN) cat $(BACKUP_DIR)/$(f) | gunzip \
	| $(DC) exec -T mysql sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"'
	@echo "Restore selesai"

migrate: ## Jalankan migrasi memakai image BARU, sebelum container diganti
	$(DC) run --rm --no-deps app php artisan migrate --force

up: ## Jalankan / ganti container dengan image baru
	$(DC) up -d --remove-orphans

optimize: ## Cache config, route, view, event di setiap container PHP
	@for s in $(PHP_SERVICES); do \
		echo "→ optimize $$s"; \
		$(DC) exec -T $$s php artisan optimize || exit 1; \
	done

status: ## Lihat status container
	$(DC) ps

logs: ## Ikuti log (contoh: make logs s=queue)
	$(DC) logs -f --tail=100 $(s)

shell: ## Masuk shell container app
	$(DC) exec app sh

migrate-status: ## Lihat status migrasi
	$(DC) exec app php artisan migrate:status

rollback: ## Batalkan batch migrasi terakhir
	$(DC) exec app php artisan migrate:rollback --force

down: ## Hentikan semua container (data volume tetap aman)
	$(DC) down

prune: ## Hapus image lama yang tidak terpakai
	docker image prune -f