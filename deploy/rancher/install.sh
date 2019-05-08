#!/usr/bin/env bash

sudo ros service enable https://raw.githubusercontent.com/campsych/concerto-platform/master/deploy/rancher/concerto.yml
sudo ros service up database
sudo ros service up concerto
