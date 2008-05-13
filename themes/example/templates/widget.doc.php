<?php

print mcms::html('h2', $document['name']);

print mcms::html('div', array('class' => 'body'), $document['body']);
