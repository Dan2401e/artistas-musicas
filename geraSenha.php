<?php
$senha = $argv[1] ?? '123';
echo password_hash($senha, PASSWORD_BCRYPT), PHP_EOL;