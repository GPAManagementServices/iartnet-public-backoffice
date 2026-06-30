<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IartNET institution slug (general coordination)
    |--------------------------------------------------------------------------
    | People linked to the institution with this slug are listed under
    | "general_coordination". This institution is excluded from the
    | per-institution sections. Match: exact slug in requested locale.
    */
    'iartnet_institution_slug' => env('PEOPLE_PAGE_IARTNET_SLUG', 'iartnet'),

    /*
    |--------------------------------------------------------------------------
    | Global role slugs (person.role)
    |--------------------------------------------------------------------------
    | Slug => [ 'en' => label_en, 'it' => label_it ]. Matching: exact after trim
    | on the value in the requested locale.
    */
    'global_roles' => [
        'academic_coordinator' => [
            'en' => 'Academic Coordinator',
            'it' => 'Coordinatore Scientifico',
        ],
        'research_unit_lead' => [
            'en' => 'Research Unit Lead',
            'it' => 'Responsabile Unità di Ricerca',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Institution section roles (institution_roles[].role) – 5 standard sections
    |--------------------------------------------------------------------------
    | Order of keys = display order. Matching: exact after trim on locale value.
    */
    'institution_section_roles' => [
        'academic_team_members' => [
            'en' => 'Academic Team Member',
            'it' => 'Personale Docente del Team di Progetto',
        ],
        'research_staff' => [
            'en' => 'Research Staff',
            'it' => 'Staff di Ricerca',
        ],
        'project_staff' => [
            'en' => 'Project Staff',
            'it' => 'Staff di Progetto',
        ],
        'student_collaborators' => [
            'en' => 'Student Collaborator',
            'it' => 'Studente Collaboratore',
        ],
        'external_consultant' => [
            'en' => 'External Consultant',
            'it' => 'Consulente Esterno',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dedicated section roles (shown first per institution)
    |--------------------------------------------------------------------------
    | Slug => [ 'en' => label_en, 'it' => label_it ]. Order of keys = display order.
    */
    'dedicated_section_roles' => [
        'general_advisor' => [
            'en' => 'General Advisor',
            'it' => 'Consulente Scientifico di progetto',
        ],
        'research_coordinator_communication' => [
            'en' => 'Research Coordinator and Communication',
            'it' => 'Coordinamento Ricerca e Comunicazione',
        ],
        'digital_collections_curator' => [
            'en' => 'Digital Collections Curator',
            'it' => 'Curatrice Collezioni Digitali',
        ],
        'project_manager' => [
            'en' => 'Project Manager',
            'it' => 'Project Manager',
        ],
        'chief_information_officer' => [
            'en' => 'Chief Information Officer',
            'it' => 'Responsabile Sistemi Informatici',
        ],
        'research_office_manager' => [
            'en' => 'Research Office Manager',
            'it' => 'Responsabile Ufficio Ricerca',
        ],
        'research_group_coordinator' => [
            'en' => 'Research Group Coordinator',
            'it' => 'Coordinatore Gruppo di Ricerca',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | No-role section
    |--------------------------------------------------------------------------
    | Slug for section when role is null or string "null" (after trim).
    */
    'no_role_section_slug' => 'no_role',

];
