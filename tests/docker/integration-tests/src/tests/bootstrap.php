<?php

// add any bootstrapping code in here

foreach (glob(__DIR__ . "/util/*.php") as $filename) {
    include $filename;
}
