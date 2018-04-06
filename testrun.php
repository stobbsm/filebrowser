<?php
/* Copyright (c) 2017 Matthew Stobbs <matthew@sproutingcommunications.com>

GNU GENERAL PUBLIC LICENSE
   Version 3, 29 June 2007

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */


require 'vendor/autoload.php';

use stobbsm\FileBrowser;

$start_time = getrusage();

if (extension_loaded('pthreads')) {
    printf("Extenstion pthreads is loaded\n");
} else {
    printf("Extension pthreads is not loaded\n");
}

$myfiles = new FileBrowser('/home/stobbsm/Nextcloud');

$end_time = getrusage();

// Script end
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

echo "This process used " . rutime($end_time, $start_time, "utime") .
    " ms for its computations\n";
echo "It spent " . rutime($end_time, $start_time, "stime") .
    " ms in system calls\n";