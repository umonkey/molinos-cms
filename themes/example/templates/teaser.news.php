<?php

print mcms::html('h2', l("/node/{$doc['id']}/", $doc['name']));

print mcms::html('div', array('class' => 'teaser') , $doc['teaser']);
