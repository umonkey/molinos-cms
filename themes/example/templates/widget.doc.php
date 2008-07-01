<?php

if (!empty($document) ){
	print mcms::html('h2', $document['name']);
	print $document['text'];
};