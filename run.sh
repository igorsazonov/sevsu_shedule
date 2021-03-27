#!/bin/bash

#php ./downloader.php

FILES=files/*.xlsx
for f in $FILES
do
  soffice --convert-to xls "$f" --outdir ./files/
  rm "$f"
done

FILES=files/*
for f in $FILES
do
  echo "Processing $f file..."
  php ./main.php "$f"
done
