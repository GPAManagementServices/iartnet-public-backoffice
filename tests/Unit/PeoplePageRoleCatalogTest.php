<?php

namespace Tests\Unit;

use App\Services\PeoplePageRoleCatalog;
use Tests\TestCase;

class PeoplePageRoleCatalogTest extends TestCase
{
    public function test_classifies_research_unit_lead(): void
    {
        $r = PeoplePageRoleCatalog::classifyInstitutionRoleTranslations([
            'en' => 'Research Unit Lead',
            'it' => 'Responsabile Unità di Ricerca',
        ]);
        $this->assertSame('research_unit_lead', $r['role_key']);
        $this->assertSame('Research Unit Lead', $r['role_label_en']);
    }

    public function test_classifies_from_italian_only(): void
    {
        $r = PeoplePageRoleCatalog::classifyInstitutionRoleTranslations([
            'it' => 'Staff di Ricerca',
        ]);
        $this->assertSame('research_staff', $r['role_key']);
        $this->assertSame('Research Staff', $r['role_label_en']);
    }

    public function test_empty_role_is_no_role(): void
    {
        $r = PeoplePageRoleCatalog::classifyInstitutionRoleTranslations([]);
        $this->assertSame('no_role', $r['role_key']);
        $this->assertNull($r['role_label_en']);
    }

    public function test_exact_labels_include_global_and_sections(): void
    {
        $labels = PeoplePageRoleCatalog::exactLabelsForRoleKey('academic_coordinator');
        $this->assertContains('Academic Coordinator', $labels);

        $rul = PeoplePageRoleCatalog::exactLabelsForRoleKey('research_unit_lead');
        $this->assertContains('Research Unit Lead', $rul);
    }

    public function test_classifies_research_group_coordinator_dedicated(): void
    {
        $r = PeoplePageRoleCatalog::classifyInstitutionRoleTranslations([
            'en' => 'Research Group Coordinator',
            'it' => 'Coordinatore Gruppo di Ricerca',
        ]);
        $this->assertSame('research_group_coordinator', $r['role_key']);
        $this->assertSame('Research Group Coordinator', $r['role_label_en']);
    }
}
