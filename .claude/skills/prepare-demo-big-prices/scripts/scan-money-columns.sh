#!/usr/bin/env bash
# Lists money-like integer columns from the MERGED Propel schema (run propel:schema:copy first).
# Output: <table>.<column> <TYPE> [phpType]
set -euo pipefail
cd "$(git rev-parse --show-toplevel)/src/Orm/Propel/Schema"
awk '
/<table /  { match($0, /name="[^"]+"/); t = substr($0, RSTART + 6, RLENGTH - 7) }
/<column / && /type="(INTEGER|BIGINT)"/ && /name="[^"]*(price|total|amount|sum|fee|threshold|budget|commission)[^"]*"/ {
    match($0, /name="[^"]+"/); c = substr($0, RSTART + 6, RLENGTH - 7)
    match($0, /type="[^"]+"/); ty = substr($0, RSTART + 6, RLENGTH - 7)
    php = ""; if (match($0, /phpType="[^"]+"/)) php = substr($0, RSTART, RLENGTH)
    if (c !~ /^(id_|fk_)/ && t !~ /_(storage|search)$/) print t "." c " " ty " " php
}' *.schema.xml | sort
