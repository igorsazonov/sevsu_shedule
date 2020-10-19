#!/bin/bash

php ./downloader.php

FILES=files/*
for f in $FILES
do
  echo "Processing $f file..."
  php ./main.php "$f"
done