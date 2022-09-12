<?php
return [
    'connector-slack' => [ 'tests' => false ],
    'package-comments' => [ 'tests' => false ],
    'package-googleplaces' => [ 'tests' => false ],
    'package-process-documenter' => [ 'tests' => false ],
    'package-process-optimization' => [ 'tests' => false ],
    'package-sentry' => [ 'tests' => false ],
    'package-signature' => [ 'tests' => false ],
    'packages' => [ 'tests' => false ],
    
    'package-versions' => [ 'tests' => true ],
    'package-translations' => [ 'tests' => true ],
    
    // Add new packages here or enable tests only after unit test fixes are merged into develop
    'connector-send-email' => [ 'tests' => false ],
    'package-collections' => [ 'tests' => false ],
    'package-savedsearch' => [ 'tests' => false ],
];
