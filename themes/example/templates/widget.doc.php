<?php

if (!empty($document) ){
	print html::em('h2', $document['name']);
	print $document['text'];
};
