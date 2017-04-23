<?php

chdir(getenv('PW_PATH'));
touch('testfile');
shell_exec('composer require psr/log');