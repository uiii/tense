#!/bin/bash

TEST_DIR=$(cd "$(dirname "$0")"; pwd)

cd "$TEST_DIR/../../../example"
php ../tense run --no-ansi > "$TEST_DIR/current-out.log" || true
cd "$TEST_DIR"
cat out.log
cat current-out.log

# upload current output to a file share
curl -F "file=@current-out.log" https://file.io

diff <(cat out.log | sed -e '/Time/d;/\.php:[0-9]/d;/Using/d') <(cat current-out.log | sed -e '/Time/d;/\.php:[0-9]/d;/Using/d')