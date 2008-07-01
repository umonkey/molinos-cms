<?php

if (!empty($documents)) {
	if (count($documents) == 1){
		print '<h2>' . $documents[0]['name'] . '</h2>';
		print $documents[0]['text'];
	} else {
		print '<ul class="articles">';
		foreach ($documents as $doc){
			print '<li>';
			print '<h2><a href="node/' . $doc['id'] . '/">' . $doc['name'] . '</a></h2>';
			print $doc['teaser'];
			print '</li>';
		};
		print '<ul>';
	};
};