SHELL := /bin/bash

all: help

help:
	@echo "Available targets:"
	@awk '/^[a-zA-Z0-9_-]+:/ {print "  " $$1}' $(MAKEFILE_LIST)

.PHONY: update

update:
	bash ./scripts/services-pull-images.sh "$$@"
