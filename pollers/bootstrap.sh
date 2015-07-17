#!/usr/bin/env bash

set -eu -o pipefail

exec php "/usr/src/cloudprocessingengine/src/$1.php" \
     "${@:2}"
