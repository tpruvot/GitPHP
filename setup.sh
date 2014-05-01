#!/bin/bash

chgrp -R www-data * 
chmod -R g+w templates_c
chmod -R g+w cache

if [ ! -f "config/gitphp.conf.php" ]; then
	cp config/gitphp.conf.php.example config/gitphp.conf.php
fi

if [ ! -f "config/projects.conf.php" ]; then
	cp config/projects.conf.php.example config/projects.conf.php
fi

