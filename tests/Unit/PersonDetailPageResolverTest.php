<?php

namespace Tests\Unit;

use App\Models\Person;
use App\Support\PersonDetailPageResolver;
use Tests\TestCase;

class PersonDetailPageResolverTest extends TestCase
{
    public function test_true_when_published_slug_and_meaningful_shortbio(): void
    {
        $person = new Person(['status' => 'published']);
        $person->setTranslation('slug', 'en', 'jane-doe');
        $person->setTranslation('shortbio', 'en', '<p>Curriculum summary.</p>');

        $this->assertTrue(PersonDetailPageResolver::hasDetailPage($person, 'en'));
    }

    public function test_false_when_shortbio_empty_after_strip(): void
    {
        $person = new Person(['status' => 'published']);
        $person->setTranslation('slug', 'en', 'jane-doe');
        $person->setTranslation('shortbio', 'en', '<p><br></p>');

        $this->assertFalse(PersonDetailPageResolver::hasDetailPage($person, 'en'));
    }

    public function test_false_when_slug_missing(): void
    {
        $person = new Person(['status' => 'published']);
        $person->setTranslation('slug', 'en', '');
        $person->setTranslation('shortbio', 'en', '<p>Bio</p>');

        $this->assertFalse(PersonDetailPageResolver::hasDetailPage($person, 'en'));
    }

    public function test_false_when_not_published(): void
    {
        $person = new Person(['status' => 'draft']);
        $person->setTranslation('slug', 'en', 'jane-doe');
        $person->setTranslation('shortbio', 'en', '<p>Bio</p>');

        $this->assertFalse(PersonDetailPageResolver::hasDetailPage($person, 'en'));
    }
}
