<?php

print html::em('h2', l("/node/{$doc['id']}/", $doc['name']));

print html::em('div', array('class' => 'teaser') , $doc['teaser']);
