<?php

/**
 * حصتك public sitemap — expandable IA.
 * Add a node here (+ lang keys) to publish a new public page without rewriting nav.
 *
 * Keys are stable IDs. `uri` is the public path. `nav` = show in primary nav.
 * Children inherit expandability; set enabled=false to hide without deleting.
 */
return [

    'home' => [
        'uri' => '/',
        'route' => 'home',
        'nav' => true,
        'order' => 10,
    ],

    'about' => [
        'uri' => '/about',
        'route' => 'public.about',
        'nav' => true,
        'order' => 20,
        'view' => 'public.site.show',
    ],

    'teacher-development' => [
        'enabled' => false,
        'uri' => '/teacher-development',
        'route' => 'public.site.teacher-development',
        'nav' => true,
        'order' => 30,
        'view' => 'public.site.show',
        'children' => [
            'teacher-paths' => [
                'uri' => '/teacher-development/paths',
                'route' => 'public.site.teacher-paths',
                'view' => 'public.site.show',
            ],
            'teacher-courses' => [
                'uri' => '/teacher-development/courses-workshops',
                'route' => 'public.site.teacher-courses',
                'view' => 'public.site.show',
            ],
            'teacher-resources' => [
                'uri' => '/teacher-development/resources',
                'route' => 'public.site.teacher-resources',
                'view' => 'public.site.show',
            ],
        ],
    ],

    'consultations' => [
        'enabled' => false,
        'uri' => '/educational-consultations',
        'route' => 'public.site.consultations',
        'nav' => true,
        'order' => 40,
        'view' => 'public.site.show',
        'children' => [
            'consultations-teachers' => [
                'uri' => '/educational-consultations/teachers',
                'route' => 'public.site.consultations-teachers',
                'view' => 'public.site.show',
            ],
            'consultations-specialized' => [
                'uri' => '/educational-consultations/specialized',
                'route' => 'public.site.consultations-specialized',
                'view' => 'public.site.show',
            ],
        ],
    ],

    'institutional' => [
        'enabled' => false,
        'uri' => '/institutional',
        'route' => 'public.site.institutional',
        'nav' => true,
        'order' => 50,
        'view' => 'public.site.show',
        'children' => [
            'institutional-schools' => [
                'uri' => '/institutional/schools',
                'route' => 'public.site.institutional-schools',
                'view' => 'public.site.show',
            ],
            'institutional-training' => [
                'uri' => '/institutional/training',
                'route' => 'public.site.institutional-training',
                'view' => 'public.site.show',
            ],
            'institutional-solutions' => [
                'uri' => '/institutional/solutions',
                'route' => 'public.site.institutional-solutions',
                'view' => 'public.site.show',
            ],
        ],
    ],

    'workshops' => [
        'enabled' => false,
        'uri' => '/workshops-courses',
        'route' => 'public.site.workshops',
        'nav' => true,
        'order' => 60,
        'view' => 'public.site.show',
    ],

    'resources' => [
        'enabled' => false,
        'uri' => '/resources',
        'route' => 'public.site.resources',
        'nav' => true,
        'order' => 70,
        'view' => 'public.site.show',
        'children' => [
            'resources-materials' => [
                'uri' => '/resources/materials',
                'route' => 'public.site.resources-materials',
                'view' => 'public.site.show',
            ],
            'resources-templates' => [
                'uri' => '/resources/templates',
                'route' => 'public.site.resources-templates',
                'view' => 'public.site.show',
            ],
            'resources-tools' => [
                'uri' => '/resources/tools',
                'route' => 'public.site.resources-tools',
                'view' => 'public.site.show',
            ],
        ],
    ],

    'assessment' => [
        'enabled' => false,
        'uri' => '/assessment',
        'route' => 'public.site.assessment',
        'nav' => true,
        'order' => 80,
        'view' => 'public.site.show',
        'children' => [
            'assessment-diagnosis' => [
                'uri' => '/assessment/diagnosis',
                'route' => 'public.site.assessment-diagnosis',
                'view' => 'public.site.show',
            ],
            'assessment-recommendations' => [
                'uri' => '/assessment/recommendations',
                'route' => 'public.site.assessment-recommendations',
                'view' => 'public.site.show',
            ],
        ],
    ],

    'account' => [
        'enabled' => false,
        'uri' => '/account-progress',
        'route' => 'public.site.account',
        'nav' => true,
        'order' => 90,
        'view' => 'public.site.show',
        'children' => [
            'account-profile' => [
                'uri' => '/account-progress/profile',
                'route' => 'public.site.account-profile',
                'view' => 'public.site.show',
            ],
            'account-programs' => [
                'uri' => '/account-progress/programs',
                'route' => 'public.site.account-programs',
                'view' => 'public.site.show',
            ],
            'account-tracking' => [
                'uri' => '/account-progress/tracking',
                'route' => 'public.site.account-tracking',
                'view' => 'public.site.show',
            ],
            'account-certificates' => [
                'uri' => '/account-progress/certificates',
                'route' => 'public.site.account-certificates',
                'view' => 'public.site.show',
            ],
        ],
    ],

    'certificates' => [
        'enabled' => false,
        'uri' => '/achievements',
        'route' => 'public.site.certificates',
        'nav' => true,
        'order' => 100,
        'view' => 'public.site.show',
    ],

    'contact' => [
        'uri' => '/contact',
        'route' => 'public.contact',
        'nav' => true,
        'order' => 110,
        'view' => 'public.site.contact',
    ],

    // Keep existing path page as utility (not top-nav clutter; linked from teacher-dev)
    'path' => [
        'uri' => '/path',
        'route' => 'public.path',
        'nav' => false,
        'order' => 35,
        'view' => 'public.path',
    ],
];
